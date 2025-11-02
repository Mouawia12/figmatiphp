<?php
declare(strict_types=1);
require_once __DIR__ . '/../../inc/functions.php';
require_once __DIR__ . '/../../inc/rag.php';

header('Content-Type: application/json; charset=utf-8');

function openai_chat_complete(string $system, string $user): string {
  $apiKey = (string)env('OPENAI_API_KEY', '');
  if ($apiKey === '') throw new RuntimeException('OPENAI_API_KEY missing');
  $model = (string)env('OPENAI_MODEL', 'gpt-4o-mini');
  $payload = [
    'model' => $model,
    'messages' => [
      ['role' => 'system', 'content' => $system],
      ['role' => 'user',   'content' => $user],
    ],
    'temperature' => 0.2,
  ];
  $ch = curl_init('https://api.openai.com/v1/chat/completions');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . $apiKey,
      'Content-Type: application/json',
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT => 60,
  ]);
  $res = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($res === false) { $err = curl_error($ch); curl_close($ch); throw new RuntimeException('OpenAI error: ' . $err); }
  curl_close($ch);
  $j = json_decode((string)$res, true);
  if ($code >= 400) throw new RuntimeException('OpenAI HTTP ' . $code . ': ' . (($j['error']['message'] ?? '') . ' ' . json_encode($j)));
  return (string)($j['choices'][0]['message']['content'] ?? '');
}

try {
  $me = require_admin();
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
  }
  if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
  }

  $message = trim((string)($_POST['message'] ?? ''));
  $conversation_id = trim((string)($_POST['conversation_id'] ?? ''));
  if ($message === '') {
    echo json_encode(['reply' => 'اكتب سؤالك من فضلك.', 'sources' => [], 'tool_calls' => [], 'conversation_id' => $conversation_id]);
    exit;
  }

  // 1) RAG Search
  $collection = (string)env('RAG_COLLECTION', 'crosing_ar');
  $results = [];
  try {
    $results = rag_search($collection, $message, 8, null);
  } catch (Throwable $e) {
    // أكمل بدون سياق إذا تعذر الاسترجاع
    $results = [];
  }
  $contextBlocks = [];
  $sources = [];
  foreach ($results as $i => $r) {
    $title = (string)($r['meta']['title'] ?? ($r['meta']['source'] ?? 'مقطع #' . ($i+1)));
    $contextBlocks[] = "[{$i+1}] العنوان: {$title}\n{$r['content']}";
    $sources[] = [
      'title' => $title,
      'url' => (string)($r['meta']['path_or_url'] ?? ''),
      'score' => (float)($r['score'] ?? 0),
    ];
  }
  $contextText = $contextBlocks ? ("سياق مسترجع (استخدمه بدقة ولا تفترض):\n" . implode("\n\n", $contextBlocks)) : '';

  // تتبّع الإحصائيات: أكثر الأسئلة وتحديد ما إذا كانت بلا مصادر
  try { track_chat_question($message, !empty($sources)); } catch (Throwable $e) { /* ignore */ }

  // 2) System Prompt بالعربية مع أسلوب قصير ومتزن وتعاطف بسيط وCTA
  $system =
    "أنت مساعد دعم فني عربي لشركة عزم الإنجاز.\n" .
    "القواعد: \n" .
    "- كن مهنيًا وودودًا، وردّ بجُمل قصيرة طبيعية (3–5 جُمل).\n" .
    "- لا تخمّن أبدًا. اعتمد فقط على (السياق المسترجع) أو نتيجة أداة.\n" .
    "- إن لم يكفِ السياق، لا تُجب بمعلومة. اطلب تحديد المطلوب أو اقترح تحويلًا لموظف.\n" .
    "- اختم بسطر (CTA) واضح مثل: هل تحب أرتّب لك الطلب الآن؟\n" .
    "- لا تقدّم أسعار/سياسات غير موثقة.\n" .
    "- اجعل الرد ≤ 1800 حرف.\n";

  // 3) بناء رسالة المستخدم مع السياق
  $userMsg = $contextText === '' ? $message : ($message . "\n\n---\n" . $contextText . "\n---\n");

  // 4) توليد الرد
  $reply = '';
  try {
    // إذا لم توجد مصادر، لا نقدّم جوابًا قطعيًا؛ نطلب توضيحًا
    if (empty($sources)) {
      $reply = "حتى أساعدك بدقة وبدون تخمين، أحتاج تفاصيل أكثر أو مستند ذي صلة (مثل السياسة أو رقم الطلب). هل تود تحويل سؤالك لموظف الآن؟";
    } else {
      $reply = openai_chat_complete($system, $userMsg);
    }
  } catch (Throwable $e) {
    $reply = "تعذر توليد الرد آليًا حاليًا. حاول لاحقًا أو حدد سؤالك بدقة أكثر.";
  }

  // 5) إلحاق المصادر المختصرة في نهاية الرد إن وُجدت
  if (!empty($sources)) {
    $titles = array_values(array_filter(array_map(function($s){ return (string)($s['title'] ?? ''); }, $sources)));
    if ($titles) {
      $suffix = "\nالمصدر: " . implode('، ', array_slice($titles, 0, 3));
      $reply .= $suffix;
    }
  }

  // قصّ الرد للاحتياط
  if (mb_strlen($reply) > 1800) { $reply = mb_substr($reply, 0, 1790) . '…'; }

  echo json_encode([
    'reply' => $reply,
    'sources' => $sources,
    'tool_calls' => [],
    'conversation_id' => $conversation_id ?: bin2hex(random_bytes(8)),
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Server Error', 'detail' => $e->getMessage()]);
}

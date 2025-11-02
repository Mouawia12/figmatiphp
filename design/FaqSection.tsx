import React, { useState } from "react";
import FAQItem from "./FAQ-item.png";

interface FAQItemData {
  id: number;
  question: string;
  answer?: string;
}

export const FaqSection = (): JSX.Element => {
  const faqItems: FAQItemData[] = [
    {
      id: 1,
      question: "ماهو الدفع الآجل (BNPL)؟",
      answer: "",
    },
    {
      id: 2,
      question: "من يمكنه الاستفادة من خدمة الدفع الآجل؟",
      answer: "",
    },
    {
      id: 3,
      question: "ما هي المنتجات التي تقدمها شركة عزم الإنجاز؟",
      answer: "",
    },
    {
      id: 4,
      question: "ما هي خدمات التصميم الداخلي التي تقدمها الشركة؟",
      answer: "",
    },
    {
      id: 5,
      question: "هل توفرون توصيل للمنتجات؟",
      answer: "",
    },
    {
      id: 6,
      question: "هل يمكنني الحصول على استشارة قبل الشراء؟",
      answer: "",
    },
  ];

  const [expandedId, setExpandedId] = useState<number | null>(null);

  const toggleFAQ = (id: number) => {
    setExpandedId(expandedId === id ? null : id);
  };

  const faqPositions = [
    { top: "-top-px", left: "-left-px" },
    { top: "top-[88px]", left: "-left-px" },
    { top: "top-[175px]", left: "left-px" },
    { top: "top-[261px]", left: "left-px" },
    { top: "top-[347px]", left: "left-px" },
    { top: "top-[432px]", left: "left-px" },
  ];

  const questionPositions = [
    { top: "top-0", left: "left-[68.81%]", width: "w-[26.44%]" },
    { top: "top-[19.22%]", right: "right-[58px]", width: "w-[529px]" },
    { top: "top-[36.81%]", left: "left-[53.47%]", width: "w-[41.83%]" },
    { top: "top-[52.56%]", left: "left-[41.98%]", width: "w-[53.27%]" },
    { top: "top-[71.17%]", left: "left-[47.39%]", width: "w-[47.86%]" },
    { top: "top-[88.55%]", left: "left-[32.90%]", width: "w-[62.34%]" },
  ];

  return (
    <section
      className="absolute h-[657px] top-[4255px] left-[120px] flex items-start min-w-[1200px]"
      aria-labelledby="faq-heading"
    >
      <div className="w-[1200px] h-[657px] relative">
        <div className="absolute h-full top-0 left-[calc(50.00%_-_600px)] w-[1202px]">
          <div className="absolute top-[168px] left-0 w-[1212px] h-[489px]">
            {faqItems.map((item, index) => (
              <button
                key={item.id}
                onClick={() => toggleFAQ(item.id)}
                className={`${faqPositions[index].top} ${faqPositions[index].left} ${index === 0 ? "h-[58px]" : index === 4 ? "h-[59px]" : index === 2 ? "h-[58px]" : index === 5 ? "h-[58px]" : "h-[59px]"} absolute w-[1201px] bg-white rounded-[30px] border border-solid ${index === 0 ? "border-[#1c75bc]" : "border-[#104d88]"} blur-[1px] cursor-pointer transition-all hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#1c75bc] focus:ring-offset-2`}
                aria-expanded={expandedId === item.id}
                aria-controls={`faq-answer-${item.id}`}
              />
            ))}

            {faqItems.map((item, index) => (
              <div
                key={`question-${item.id}`}
                className={`${questionPositions[index].top} ${questionPositions[index].left || ""} ${questionPositions[index].right || ""} ${questionPositions[index].width} ${index === 0 || index === 4 || index === 5 ? "h-[9.99%]" : index === 1 ? "h-[9.99%]" : "h-[10.02%]"} absolute [font-family:'Cairo-SemiBold',Helvetica] font-semibold text-[#3172b4] text-[25px] tracking-[0] leading-[normal] [direction:rtl] pointer-events-none`}
              >
                {item.question}
              </div>
            ))}
          </div>

          <h2
            id="faq-heading"
            className="absolute h-[14.31%] top-0 left-[calc(50.00%_-_247px)] w-[493px] [font-family:'Cairo-Bold',Helvetica] font-bold text-[#104d88] text-5xl text-center tracking-[0] leading-[normal] [direction:rtl]"
          >
            الأسئلة الشائعة
          </h2>
        </div>

        <img
          className="absolute w-[30px] h-[450px] top-[187px] left-[46px]"
          alt="FAQ decoration"
          src={FAQItem}
          aria-hidden="true"
        />
      </div>
    </section>
  );
};

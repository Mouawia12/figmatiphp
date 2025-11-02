// Enhance all .dnd-zone or file inputs
(function() {
  function prevent(e) { e.preventDefault(); e.stopPropagation(); }
  function highlight(el, on) { el.classList.toggle('dnd-over', !!on); }

  function bindZone(zone) {
    const input = zone.querySelector('input[type="file"]');
    if (!input) return;

    const parent = zone.parentNode;
    let confirmationEl = null;

    function removeSelectedFile() {
      input.value = ''; // Clear the file input
      if (confirmationEl) {
        confirmationEl.remove();
        confirmationEl = null;
      }
      zone.style.display = 'block'; // Show the drop zone again
    }

    function showConfirmation(file) {
      // Remove previous confirmation if any
      if (confirmationEl) {
        confirmationEl.remove();
      }

      confirmationEl = document.createElement('div');
      confirmationEl.className = 'file-confirmation alert alert-info p-2 mt-2'; // Using bootstrap alert for styling
      
      const content = document.createElement('div');
      content.className = 'd-flex justify-content-between align-items-center';

      const fileNameEl = document.createElement('span');
      fileNameEl.textContent = file.name;
      
      const deleteBtn = document.createElement('button');
      deleteBtn.type = 'button';
      deleteBtn.className = 'btn-close'; // Bootstrap close button
      deleteBtn.setAttribute('aria-label', 'Close');
      deleteBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        removeSelectedFile();
      });

      content.appendChild(fileNameEl);
      content.appendChild(deleteBtn);
      confirmationEl.appendChild(content);
      
      // Insert after the zone
      zone.parentNode.insertBefore(confirmationEl, zone.nextSibling);

      // Hide the drop zone itself
      zone.style.display = 'none';
    }

    ['dragenter', 'dragover'].forEach(ev => zone.addEventListener(ev, e => { prevent(e); highlight(zone, true); }));
    ['dragleave', 'dragend', 'drop'].forEach(ev => zone.addEventListener(ev, e => { prevent(e); highlight(zone, false); }));

    zone.addEventListener('drop', (e) => {
      prevent(e);
      const files = e.dataTransfer && e.dataTransfer.files ? e.dataTransfer.files : null;
      if (files && files.length > 0) {
        input.files = files;
        showConfirmation(files[0]);
      }
    });

    input.addEventListener('change', (e) => {
      const files = e.target.files;
      if (files && files.length > 0) {
        showConfirmation(files[0]);
      }
    });

    // Click through
    zone.addEventListener('click', () => input.click());
  }

  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.dnd-zone').forEach(bindZone);
    document.querySelectorAll('input[type="file"]:not(.dnd-input)').forEach(function(inp) {
      if (inp.closest('.dnd-zone')) return;
      const wrap = document.createElement('label');
      wrap.className = 'dnd-zone form-control form-control-sm input-soft';
      wrap.style.cursor = 'pointer';
      inp.classList.add('visually-hidden', 'dnd-input');
      const msg = document.createElement('div');
      msg.className = 'small text-muted';
      msg.textContent = 'اسحب وأفلت الملف هنا أو اضغط للاختيار';
      const parent = inp.parentNode;
      parent.insertBefore(wrap, inp);
      wrap.appendChild(inp);
      wrap.appendChild(msg);
      bindZone(wrap);
    });
  });
})();

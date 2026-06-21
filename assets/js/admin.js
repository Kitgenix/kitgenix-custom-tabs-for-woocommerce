(function () {
  'use strict';

  function cfg() {
    return window.kitgenix_custom_tabs_for_woocommerce_admin || {};
  }

  function i18n(key, fallback) {
    try {
      var c = cfg();
      return (c && c.i18n && c.i18n[key]) ? String(c.i18n[key]) : fallback;
    } catch (_e) {
      return fallback;
    }
  }

  function slugify(input) {
    input = (input || '').toString().trim().toLowerCase();
    if (!input) return '';
    if (input.normalize) input = input.normalize('NFKD');
    input = input.replace(/[\u0300-\u036f]/g, '');
    input = input.replace(/[^a-z0-9\s-]/g, '');
    input = input.replace(/\s+/g, '-');
    input = input.replace(/-+/g, '-');
    input = input.replace(/^-|-$/g, '');
    return input;
  }

  function toInt(val, def) {
    var n = parseInt(val, 10);
    return isNaN(n) ? def : n;
  }

  function parseTemplates(raw) {
    if (!raw) return [];
    try {
      var parsed = JSON.parse(String(raw));
      return Array.isArray(parsed) ? parsed : [];
    } catch (_e) {
      return [];
    }
  }

  function appendCopySuffix(text) {
    text = String(text || '').trim();
    if (!text) return i18n('copyLabel', 'Copy');
    if (/\((copy)\)$/i.test(text)) return text;
    return text + ' (' + i18n('copyLabel', 'Copy') + ')';
  }

  function computePriorityForIndex(base, step, index) {
    var p = base + (step * index);
    // Mirror PHP clamp logic for the "between" presets.
    if (base > 10 && base < 20) return Math.min(p, 19);
    if (base > 20 && base < 30) return Math.min(p, 29);
    return p;
  }

  function hasQuill() {
    return !!(window.Quill && typeof window.Quill === 'function');
  }

  function ensureModalEditorMarkup(slot) {
    if (!slot) return null;
    var existing = slot.querySelector('#kitgenix_custom_tabs_for_woocommerce_modal_editor');
    if (existing) return existing;

    var wrap = document.createElement('div');
    wrap.className = 'kitgenix-custom-tabs-for-woocommerce-quill';

    var toolbar = document.createElement('div');
    toolbar.className = 'kitgenix-custom-tabs-for-woocommerce-quill-toolbar';
    toolbar.setAttribute('data-kitgenix-custom-tabs-for-woocommerce-quill-toolbar', '1');
    toolbar.innerHTML = ''
      + '<span class="ql-formats">'
      + '<select class="ql-header"><option selected></option><option value="2"></option><option value="3"></option></select>'
      + '</span>'
      + '<span class="ql-formats">'
      + '<button type="button" class="ql-bold"></button>'
      + '<button type="button" class="ql-italic"></button>'
      + '<button type="button" class="ql-underline"></button>'
      + '<button type="button" class="ql-strike"></button>'
      + '</span>'
      + '<span class="ql-formats">'
      + '<button type="button" class="ql-list" value="ordered"></button>'
      + '<button type="button" class="ql-list" value="bullet"></button>'
      + '<button type="button" class="ql-link"></button>'
      + '</span>'
      + '<span class="ql-formats">'
      + '<button type="button" class="ql-clean"></button>'
      + '</span>';

    var editor = document.createElement('div');
    editor.className = 'kitgenix-custom-tabs-for-woocommerce-quill-editor';
    editor.setAttribute('data-kitgenix-custom-tabs-for-woocommerce-quill-editor', '1');
    editor.style.minHeight = '190px';

    var hidden = document.createElement('textarea');
    hidden.id = 'kitgenix_custom_tabs_for_woocommerce_modal_editor';
    hidden.className = 'kitgenix-custom-tabs-for-woocommerce-quill-hidden';
    hidden.setAttribute('data-kitgenix-custom-tabs-for-woocommerce-modal-field', 'content');
    hidden.hidden = true;

    wrap.appendChild(toolbar);
    wrap.appendChild(editor);
    wrap.appendChild(hidden);
    slot.appendChild(wrap);

    return hidden;
  }

  function normalizeHtml(html) {
    html = String(html || '').trim();
    if (!html || html === '<p><br></p>') return '';
    return html;
  }

  function ensureEditor(textarea) {
    if (!textarea || !textarea.id || !hasQuill()) return;
    if (textarea._kitgenixQuill) return;

    var container = textarea.parentElement;
    if (!container) return;
    var toolbar = container.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-quill-toolbar="1"]');
    var editorEl = container.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-quill-editor="1"]');
    if (!toolbar || !editorEl) return;

    var quill = new window.Quill(editorEl, {
      theme: 'snow',
      modules: {
        toolbar: toolbar
      }
    });

    quill.on('text-change', function () {
      textarea.value = normalizeHtml(quill.root.innerHTML);
    });

    textarea._kitgenixQuill = quill;
  }

  function getEditorContent(textarea) {
    if (!textarea) return '';
    var quill = textarea._kitgenixQuill;
    if (quill && quill.root) {
      return normalizeHtml(quill.root.innerHTML);
    }
    return normalizeHtml(textarea.value || '');
  }

  function setEditorContent(textarea, html) {
    if (!textarea) return;
    var normalized = normalizeHtml(html);
    textarea.value = normalized;
    var quill = textarea._kitgenixQuill;
    if (quill && quill.root) {
      quill.root.innerHTML = normalized || '<p><br></p>';
    }
  }

  function ensureVisualEditorReady(textarea) {
    if (!textarea) return;
    ensureEditor(textarea);
  }

  function openModal(modal) {
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('is-open');
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'true');
    modal.classList.remove('is-open');
  }

  function initTabsManager(root) {
    if (!root) return;

    var modal = null;
    var modalTitle = null;
    var modalIndex = null;
    var titleEl = null;
    var nicknameEl = null;
    var slugEl = null;
    var priorityEl = null;
    var contentEl = null;
    var saveBtn = null;
    var cancelBtn = null;
    var slugGenBtn = null;
    var onDocKeydownCapture = null;
    var onModalClick = null;
    var onModalKeydown = null;
    var onTitleInput = null;
    var onSlugInput = null;
    var onTitleSlugInput = null;
    var onSlugGenClick = null;
    var onSaveClick = null;

    var addBtn = root.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-add="1"]');
    if (!addBtn) {
      try {
        var productPanel = root.closest('#kitgenix_custom_tabs_for_woocommerce_tabs');
        if (productPanel) addBtn = productPanel.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-add="1"]');
      } catch (_e0) {}
    }
    var tbody = root.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-body="1"]');
    var fieldsWrap = root.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-fields-wrap="1"]');
    if (!addBtn || !tbody || !fieldsWrap) return;

    var base = root.getAttribute('data-kitgenix-custom-tabs-for-woocommerce-base') || 'kitgenix_custom_tabs_for_woocommerce_tabs';
    var maxTabs = toInt(root.getAttribute('data-kitgenix-custom-tabs-for-woocommerce-max'), toInt(cfg().maxTabs, 10));
    var priorityBase = toInt(cfg().priorityBase, 50);
    var priorityStep = toInt(cfg().priorityStep, 10);
    var templates = parseTemplates(root.getAttribute('data-kitgenix-custom-tabs-for-woocommerce-templates'));

    var slugDirty = false;

    function ensureRowActions(row) {
      if (!row) return;
      var actions = row.querySelector('.kitgenix-custom-tabs-for-woocommerce-actions');
      if (!actions) return;
      if (actions.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-duplicate="1"]')) return;

      var duplicateBtn = document.createElement('a');
      duplicateBtn.href = '#';
      duplicateBtn.className = 'button button-small';
      duplicateBtn.setAttribute('data-kitgenix-custom-tabs-for-woocommerce-duplicate', '1');
      duplicateBtn.textContent = i18n('duplicateTab', 'Duplicate');

      var removeBtn = actions.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-remove="1"]');
      if (removeBtn) {
        actions.insertBefore(duplicateBtn, removeBtn);
      } else {
        actions.appendChild(duplicateBtn);
      }
    }

    function buildDataFromFields(fields) {
      return {
        title: getFieldValue(fields, 'title'),
        nickname: getFieldValue(fields, 'nickname'),
        slug: getFieldValue(fields, 'slug'),
        priority: getFieldValue(fields, 'priority'),
        content: getFieldContent(fields)
      };
    }

    function insertRowData(data) {
      if (currentCount() >= maxTabs) {
        window.alert(i18n('maxReached', 'Maximum tabs reached.'));
        return null;
      }

      clearEmptyMessage();

      var index = nextIndex();
      var fields = ensureFields(index);
      var priority = String(data.priority || '').trim();

      if (!priority) {
        priority = String(computePriorityForIndex(priorityBase, priorityStep, currentCount()));
      }

      setFieldValue(fields, 'title', String(data.title || '').trim());
      setFieldValue(fields, 'nickname', String(data.nickname || '').trim());
      setFieldValue(fields, 'slug', String(data.slug || '').trim());
      setFieldValue(fields, 'priority', priority);
      setFieldContent(fields, String(data.content || ''));

      syncRowFromFields(index);
      return index;
    }

    function duplicateRow(index) {
      var fields = getFields(index);
      if (!fields) return;

      var data = buildDataFromFields(fields);
      if (String(data.nickname || '').trim()) {
        data.nickname = appendCopySuffix(data.nickname);
      } else {
        data.title = appendCopySuffix(data.title);
      }

      if (String(data.slug || '').trim()) {
        data.slug = slugify(String(data.slug) + '-copy');
      }

      insertRowData(data);
    }

    function ensureTemplateToolbar() {
      if (!templates.length) return;
      if (root.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-template-toolbar="1"]')) return;

      var toolbar = document.createElement('div');
      toolbar.className = 'kitgenix-custom-tabs-for-woocommerce-toolbar';
      toolbar.setAttribute('data-kitgenix-custom-tabs-for-woocommerce-template-toolbar', '1');

      var label = document.createElement('span');
      label.className = 'kitgenix-custom-tabs-for-woocommerce-toolbar__label';
      label.textContent = i18n('templateToolbarLabel', 'Template library');
      toolbar.appendChild(label);

      var controls = document.createElement('div');
      controls.className = 'kitgenix-custom-tabs-for-woocommerce-toolbar__controls';

      var select = document.createElement('select');
      select.className = 'kitgenix-custom-tabs-for-woocommerce-toolbar__select';
      select.setAttribute('data-kitgenix-custom-tabs-for-woocommerce-template-picker', '1');

      var placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = i18n('chooseTemplate', 'Choose a saved template');
      select.appendChild(placeholder);

      for (var i = 0; i < templates.length; i++) {
        var option = document.createElement('option');
        option.value = String(i);
        option.textContent = String((templates[i] && (templates[i].label || templates[i].title)) || '');
        select.appendChild(option);
      }

      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'button button-secondary';
      button.setAttribute('data-kitgenix-custom-tabs-for-woocommerce-template-insert', '1');
      button.textContent = i18n('insertTemplate', 'Insert template');

      controls.appendChild(select);
      controls.appendChild(button);
      toolbar.appendChild(controls);

      var reference = root.querySelector('.kitgenix-custom-tabs-for-woocommerce-table-wrap');
      var actions = root.querySelector('.kitgenix-custom-tabs-for-woocommerce-manager-actions');
      if (actions && actions.parentNode === root) {
        if (actions.nextSibling) {
          root.insertBefore(toolbar, actions.nextSibling);
        } else {
          root.appendChild(toolbar);
        }
      } else if (reference) {
        root.insertBefore(toolbar, reference);
      } else if (root.firstChild) {
        root.insertBefore(toolbar, root.firstChild);
      } else {
        root.appendChild(toolbar);
      }

      button.addEventListener('click', function (e) {
        e.preventDefault();
        var index = toInt(select.value, -1);
        if (index < 0 || !templates[index]) return;

        insertRowData({
          title: String(templates[index].title || ''),
          nickname: String(templates[index].nickname || ''),
          slug: String(templates[index].slug || ''),
          priority: String(templates[index].priority || ''),
          content: String(templates[index].content || '')
        });

        select.value = '';
      });
    }

    function getEditorDock() {
      return null;
    }

    function getEditorSlot() {
      try {
        if (!modal) return null;
        return modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-editor-slot="1"]');
      } catch (_e) {
        return null;
      }
    }

    function dockEditorIntoModal() {
      var slot = getEditorSlot();
      if (!slot) return;
      ensureModalEditorMarkup(slot);
    }

    function returnEditorToDock() {
      // No-op with Quill. The editor stays inside the modal slot.
    }

    function bindModalEls() {
      if (!modal) return false;

      // Remove any previous handlers (modal is persistent in DOM).
      try {
        if (onDocKeydownCapture) document.removeEventListener('keydown', onDocKeydownCapture, true);
      } catch (_e0) {}
      try {
        if (onModalClick) modal.removeEventListener('click', onModalClick);
        if (onModalKeydown) modal.removeEventListener('keydown', onModalKeydown);
      } catch (_e1) {}
      try {
        if (titleEl && onTitleInput) titleEl.removeEventListener('input', onTitleInput);
        if (slugEl && onSlugInput) slugEl.removeEventListener('input', onSlugInput);
        if (titleEl && onTitleSlugInput) titleEl.removeEventListener('input', onTitleSlugInput);
        if (slugGenBtn && onSlugGenClick) slugGenBtn.removeEventListener('click', onSlugGenClick);
        if (saveBtn && onSaveClick) saveBtn.removeEventListener('click', onSaveClick);
        if (cancelBtn) cancelBtn = null;
      } catch (_e2) {}

      modalTitle = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-modal-title="1"]');
      modalIndex = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-modal-index="1"]');
      titleEl = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-modal-field="title"]');
      nicknameEl = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-modal-field="nickname"]');
      slugEl = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-modal-field="slug"]');
      priorityEl = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-modal-field="priority"]');

      // Ensure the modal has a Quill editor markup scaffold.
      dockEditorIntoModal();
      var slot = getEditorSlot();
      if (slot) {
        contentEl = ensureModalEditorMarkup(slot);
      }
      if (!contentEl) {
        contentEl = modal.querySelector('#kitgenix_custom_tabs_for_woocommerce_modal_editor') || modal.querySelector('textarea.kitgenix_custom_tabs_for_woocommerce_editor_area');
      }

      saveBtn = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-save="1"]');
      cancelBtn = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-cancel="1"]');
      slugGenBtn = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-slug-generate="1"]');
      if (!modalTitle || !modalIndex || !titleEl || !nicknameEl || !slugEl || !priorityEl || !contentEl || !saveBtn) return false;

      // Ensure editor is initialized for this injected textarea.
      ensureEditor(contentEl);
      ensureVisualEditorReady(contentEl);

      // Clear inline errors when typing.
      onTitleInput = function () { setError('title', ''); };
      titleEl.addEventListener('input', onTitleInput);

      // Auto-slug in modal.
      onSlugInput = function () {
        slugDirty = (String(slugEl.value || '').trim().length > 0);
      };
      slugEl.addEventListener('input', onSlugInput);

      onTitleSlugInput = function () {
        if (slugDirty) return;
        if (String(slugEl.value || '').trim() !== '') return;
        var s = slugify(String(titleEl.value || ''));
        if (s) slugEl.value = s;
      };
      titleEl.addEventListener('input', onTitleSlugInput);

      if (slugGenBtn) {
        onSlugGenClick = function (e) {
          e.preventDefault();
          var s = slugify(String(titleEl.value || ''));
          if (s) {
            slugEl.value = s;
            slugDirty = true;
            try { slugEl.focus(); } catch (_e) {}
          }
        };
        slugGenBtn.addEventListener('click', onSlugGenClick);
      }

      onSaveClick = function (e) {
        e.preventDefault();
        saveFromModal();
      };
      saveBtn.addEventListener('click', onSaveClick);

      try {
        onDocKeydownCapture = function (e) {
          if (!modal) return;
          if (!modal.classList || !modal.classList.contains('is-open')) return;
          if (e && (e.key === 'Escape' || e.keyCode === 27)) {
            e.preventDefault();
            requestClose();
          }
        };
        document.addEventListener('keydown', onDocKeydownCapture, true);
      } catch (_e11) {}

      // Close when clicking any close target (backdrop, close button, cancel).
      onModalClick = function (e) {
        var t = e.target;
        if (!t || !t.closest) return;
        if (t.closest('[data-kitgenix-modal-close="1"]')) {
          e.preventDefault();
          requestClose();
        }
      };
      modal.addEventListener('click', onModalClick);

      // Keyboard shortcuts inside modal.
      onModalKeydown = function (e) {
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
          e.preventDefault();
          saveFromModal();
        }
      };
      modal.addEventListener('keydown', onModalKeydown);

      return true;
    }

    function requestClose() {
      try { closeModal(modal); } catch (_e1) {}
      cleanupAfterClose();
    }

    function cleanupAfterClose() {
      try {
        if (onDocKeydownCapture) document.removeEventListener('keydown', onDocKeydownCapture, true);
      } catch (_e3) {}
      onDocKeydownCapture = null;

      try {
        if (modal && onModalClick) modal.removeEventListener('click', onModalClick);
        if (modal && onModalKeydown) modal.removeEventListener('keydown', onModalKeydown);
      } catch (_e4) {}

      try {
        if (titleEl && onTitleInput) titleEl.removeEventListener('input', onTitleInput);
        if (slugEl && onSlugInput) slugEl.removeEventListener('input', onSlugInput);
        if (titleEl && onTitleSlugInput) titleEl.removeEventListener('input', onTitleSlugInput);
        if (slugGenBtn && onSlugGenClick) slugGenBtn.removeEventListener('click', onSlugGenClick);
        if (saveBtn && onSaveClick) saveBtn.removeEventListener('click', onSaveClick);
      } catch (_e5) {}

      onModalClick = null;
      onModalKeydown = null;
      onTitleInput = null;
      onSlugInput = null;
      onTitleSlugInput = null;
      onSlugGenClick = null;
      onSaveClick = null;

      clearErrors();

      try {
        if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
      } catch (_e1) {}

      lastFocus = null;
      modal = null;
      modalTitle = null;
      modalIndex = null;
      titleEl = null;
      nicknameEl = null;
      slugEl = null;
      priorityEl = null;
      contentEl = null;
      saveBtn = null;
      cancelBtn = null;
      slugGenBtn = null;
    }

    function setError(key, msg) {
      try {
        if (!modal) return;
        var el = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-error="' + key + '"]');
        if (!el) return;
        el.textContent = msg ? String(msg) : '';
      } catch (_e) {}
    }

    function clearErrors() {
      setError('title', '');
      setError('content', '');
    }

    function currentCount() {
      return tbody.querySelectorAll('[data-kitgenix-custom-tabs-for-woocommerce-row="1"]').length;
    }

    function nextIndex() {
      var nodes = fieldsWrap.querySelectorAll('[data-kitgenix-custom-tabs-for-woocommerce-fields="1"][data-index]');
      var max = -1;
      for (var i = 0; i < nodes.length; i++) {
        var n = toInt(nodes[i].getAttribute('data-index'), -1);
        if (n > max) max = n;
      }
      return max + 1;
    }

    function getFields(index) {
      return fieldsWrap.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-fields="1"][data-index="' + String(index) + '"]');
    }

    function ensureFields(index) {
      var existing = getFields(index);
      if (existing) return existing;

      var wrap = document.createElement('div');
      wrap.setAttribute('data-kitgenix-custom-tabs-for-woocommerce-fields', '1');
      wrap.setAttribute('data-index', String(index));
      wrap.innerHTML = '' +
        '<input type="hidden" name="' + base + '[' + index + '][title]" value="" />' +
        '<input type="hidden" name="' + base + '[' + index + '][nickname]" value="" />' +
        '<input type="hidden" name="' + base + '[' + index + '][slug]" value="" />' +
        '<input type="hidden" name="' + base + '[' + index + '][priority]" value="0" />' +
        '<textarea name="' + base + '[' + index + '][content]" data-kitgenix-custom-tabs-for-woocommerce-content="1"></textarea>';
      fieldsWrap.appendChild(wrap);
      return wrap;
    }

    function getFieldValue(fields, key) {
      if (!fields) return '';
      var el = fields.querySelector('[name$="[' + key + ']"]');
      if (!el) return '';
      return String(el.value || '');
    }

    function setFieldValue(fields, key, value) {
      if (!fields) return;
      var el = fields.querySelector('[name$="[' + key + ']"]');
      if (!el) return;
      el.value = String(value || '');
    }

    function getFieldContent(fields) {
      if (!fields) return '';
      var ta = fields.querySelector('textarea[data-kitgenix-custom-tabs-for-woocommerce-content="1"]');
      return ta ? String(ta.value || '') : '';
    }

    function setFieldContent(fields, value) {
      if (!fields) return;
      var ta = fields.querySelector('textarea[data-kitgenix-custom-tabs-for-woocommerce-content="1"]');
      if (ta) ta.value = String(value || '');
    }

    function getRow(index) {
      return tbody.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-row="1"][data-index="' + String(index) + '"]');
    }

    function ensureRow(index) {
      var row = getRow(index);
      if (row) return row;

      row = document.createElement('tr');
      row.setAttribute('data-kitgenix-custom-tabs-for-woocommerce-row', '1');
      row.setAttribute('data-index', String(index));
      row.innerHTML = '' +
        '<td><strong data-kitgenix-custom-tabs-for-woocommerce-row-title="1">Untitled</strong><div class="kitgenix-custom-tabs-for-woocommerce-tabs-subtitle" data-kitgenix-custom-tabs-for-woocommerce-row-subtitle="1"></div></td>' +
        '<td><span class="kitgenix-custom-tabs-for-woocommerce-code" data-kitgenix-custom-tabs-for-woocommerce-row-slug="1">—</span></td>' +
        '<td><span data-kitgenix-custom-tabs-for-woocommerce-row-position="1">0</span></td>' +
        '<td class="kitgenix-custom-tabs-for-woocommerce-actions">' +
        '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-edit="1">Edit</a> ' +
        '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-duplicate="1">' + i18n('duplicateTab', 'Duplicate') + '</a> ' +
        '<a href="#" class="button button-link-delete" data-kitgenix-custom-tabs-for-woocommerce-remove="1">Remove</a>' +
        '</td>';
      tbody.appendChild(row);
      return row;
    }

    function syncRowFromFields(index) {
      var fields = getFields(index);
      var row = ensureRow(index);

      var title = getFieldValue(fields, 'title').trim() || 'Untitled';
      var nickname = getFieldValue(fields, 'nickname').trim();
      var displayTitle = nickname || title;
      var subtitle = (nickname && nickname !== title) ? title : '';
      var slug = getFieldValue(fields, 'slug').trim() || '—';
      var pos = getFieldValue(fields, 'priority').trim() || '0';

      var titleNode = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-row-title="1"]');
      var subNode = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-row-subtitle="1"]');
      var slugNode = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-row-slug="1"]');
      var posNode = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-row-position="1"]');
      if (titleNode) titleNode.textContent = displayTitle;
      if (subNode) subNode.textContent = subtitle;
      if (slugNode) slugNode.textContent = slug;
      if (posNode) posNode.textContent = pos;
      ensureRowActions(row);
    }

    function clearEmptyMessage() {
      try {
        var empty = root.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-empty="1"]');
        if (empty) empty.remove();
      } catch (_e) {}
    }

    function maybeShowEmptyMessage() {
      if (currentCount() > 0) return;
      if (root.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-empty="1"]')) return;
      var msg = root.getAttribute('data-kitgenix-custom-tabs-for-woocommerce-empty-message') || 'No tabs yet. Click Add Tab to create one.';

      var tr = document.createElement('tr');
      tr.className = 'kitgenix-custom-tabs-for-woocommerce-empty-row';
      tr.setAttribute('data-kitgenix-custom-tabs-for-woocommerce-empty', '1');

      var td = document.createElement('td');
      td.colSpan = 4;
      td.className = 'kitgenix-custom-tabs-for-woocommerce-empty-cell';

      var span = document.createElement('span');
      span.className = 'description';
      span.textContent = String(msg);
      td.appendChild(span);
      tr.appendChild(td);

      tbody.appendChild(tr);
    }

    var lastFocus = null;

    function loadIntoModal(index) {
      slugDirty = false;
      lastFocus = document.activeElement;

      try {
        modal = root.querySelector('#kitgenix-custom-tabs-for-woocommerce-modal')
          || document.getElementById('kitgenix-custom-tabs-for-woocommerce-modal');
      } catch (_e0) {
        modal = null;
      }
      if (!modal) return;

      openModal(modal);
      if (!bindModalEls()) {
        try { closeModal(modal); } catch (_e1) {}
        cleanupAfterClose();
        return;
      }

      clearErrors();
      var fields = (index !== null) ? getFields(index) : null;

      modalIndex.value = (index === null) ? '' : String(index);
      if (modalTitle) modalTitle.textContent = (index === null) ? 'Add Tab' : 'Edit Tab';

      var title = fields ? getFieldValue(fields, 'title') : '';
      var nickname = fields ? getFieldValue(fields, 'nickname') : '';
      var slug = fields ? getFieldValue(fields, 'slug') : '';
      var pos = fields ? getFieldValue(fields, 'priority') : '';
      var content = fields ? getFieldContent(fields) : '';

      titleEl.value = title;
      nicknameEl.value = nickname;
      slugEl.value = slug;

      if (!pos) {
        pos = String(computePriorityForIndex(priorityBase, priorityStep, currentCount()));
      }
      priorityEl.value = String(pos);

      ensureEditor(contentEl);
      ensureVisualEditorReady(contentEl);
      setEditorContent(contentEl, content);

      try { titleEl.focus(); } catch (_e) {}
    }

    function saveFromModal() {
      var index = modalIndex.value === '' ? null : toInt(modalIndex.value, null);
      var title = String(titleEl.value || '').trim();
      var nickname = String(nicknameEl.value || '').trim();
      var slug = String(slugEl.value || '').trim();
      var pos = String(priorityEl.value || '').trim();
      var content = getEditorContent(contentEl);

      clearErrors();

      if (!title) {
        setError('title', i18n('titleRequired', 'Please enter a tab title.'));
        try { titleEl.focus(); } catch (_e) {}
        return;
      }

      if (!content) {
        setError('content', i18n('contentRequired', 'Please enter tab content.'));
        return;
      }

      if (!slug) slug = slugify(title);
      if (!pos) pos = String(computePriorityForIndex(priorityBase, priorityStep, currentCount()));

      if (index === null) {
        if (currentCount() >= maxTabs) {
          window.alert(i18n('maxReached', 'Maximum tabs reached.'));
          return;
        }
        index = nextIndex();
      }

      clearEmptyMessage();

      var fields = ensureFields(index);
      setFieldValue(fields, 'title', title);
      setFieldValue(fields, 'nickname', nickname);
      setFieldValue(fields, 'slug', slug);
      setFieldValue(fields, 'priority', pos);
      setFieldContent(fields, content);

      syncRowFromFields(index);

      requestClose();
    }

    addBtn.addEventListener('click', function (e) {
      e.preventDefault();
      loadIntoModal(null);
    });

    root.addEventListener('click', function (e) {
      var t = e.target;
      if (t && t.nodeType === 3) t = t.parentElement;
      if (!t || !t.closest) return;

      var edit = t.closest('[data-kitgenix-custom-tabs-for-woocommerce-edit="1"]');
      if (edit) {
        e.preventDefault();
        var row = edit.closest('[data-kitgenix-custom-tabs-for-woocommerce-row="1"]');
        if (!row) return;
        var idx = toInt(row.getAttribute('data-index'), null);
        if (idx === null) return;
        loadIntoModal(idx);
        return;
      }

      var rm = t.closest('[data-kitgenix-custom-tabs-for-woocommerce-remove="1"]');
      if (rm) {
        e.preventDefault();
        var row2 = rm.closest('[data-kitgenix-custom-tabs-for-woocommerce-row="1"]');
        if (!row2) return;
        var idx2 = toInt(row2.getAttribute('data-index'), null);
        if (idx2 === null) return;

        if (!window.confirm(i18n('confirmRemove', 'Remove this tab?'))) return;

        try {
          var fields2 = getFields(idx2);
          if (fields2) fields2.remove();
        } catch (_e3) {}

        try { row2.remove(); } catch (_e4) {}
        maybeShowEmptyMessage();
        return;
      }

      var duplicate = t.closest('[data-kitgenix-custom-tabs-for-woocommerce-duplicate="1"]');
      if (duplicate) {
        e.preventDefault();
        var row3 = duplicate.closest('[data-kitgenix-custom-tabs-for-woocommerce-row="1"]');
        if (!row3) return;
        var idx3 = toInt(row3.getAttribute('data-index'), null);
        if (idx3 === null) return;
        duplicateRow(idx3);
        return;
      }
    });

    // Auto-slug in modal.
    // (modal event bindings happen per-open in bindModalEls)

    // If there are existing hidden fields without rows for some reason, rebuild rows.
    try {
      var allFields = fieldsWrap.querySelectorAll('[data-kitgenix-custom-tabs-for-woocommerce-fields="1"][data-index]');
      for (var i = 0; i < allFields.length; i++) {
        syncRowFromFields(toInt(allFields[i].getAttribute('data-index'), 0));
      }
    } catch (_e5) {}

    ensureTemplateToolbar();
  }

  function initPositionPresetSync() {
    try {
      var preset = document.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-position-preset="1"]');
      var posInput = document.getElementById('kitgenix_custom_tabs_for_woocommerce_priority_base');
      if (!preset || !posInput) return;

      var map = {
        before_description: 5,
        between_description_additional: 15,
        between_additional_reviews: 25,
        after_all: 35
      };

      function syncSelectToValue() {
        var v = toInt(posInput.value, NaN);
        if (isNaN(v)) return;
        for (var k in map) {
          if (Object.prototype.hasOwnProperty.call(map, k) && map[k] === v) {
            preset.value = k;
            return;
          }
        }
        preset.value = 'custom';
      }

      preset.addEventListener('change', function () {
        var key = String(preset.value || '');
        if (key === 'custom') return;
        if (Object.prototype.hasOwnProperty.call(map, key)) {
          posInput.value = String(map[key]);
        }
      });

      syncSelectToValue();
    } catch (_e) {}
  }

  function boot() {
    var roots = document.querySelectorAll('[data-kitgenix-custom-tabs-for-woocommerce-manager="1"]');
    for (var i = 0; i < roots.length; i++) initTabsManager(roots[i]);
    initPositionPresetSync();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();

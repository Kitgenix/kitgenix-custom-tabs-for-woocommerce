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
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
  }

  /* ------------------------------------------------------------------
     Unsaved-change protection
     Neither the classic product editor nor our own settings forms warn
     before navigating away with unsaved edits by default (WordPress core's
     built-in "unsaved changes" prompt only covers the block editor). Any
     form marked dirty (via a native input/change, or explicitly by the tab
     manager below for JS-driven edits like add/remove/reorder/duplicate)
     blocks the tab/window from closing until it's actually submitted.
  ------------------------------------------------------------------ */
  var kitgenixSubmittingForm = false;
  var kitgenixUnsavedGuardBound = false;

  function kitgenixMarkFormDirty(form) {
    if (form) form.setAttribute('data-kitgenix-custom-tabs-for-woocommerce-dirty', '1');
  }

  function kitgenixTrackFormSubmit(form) {
    if (!form || form._kitgenixSubmitTracked) return;
    form._kitgenixSubmitTracked = true;
    form.addEventListener('submit', function () {
      kitgenixSubmittingForm = true;
      form.removeAttribute('data-kitgenix-custom-tabs-for-woocommerce-dirty');
    });
  }

  function initUnsavedChangesGuard() {
    if (kitgenixUnsavedGuardBound) return;
    kitgenixUnsavedGuardBound = true;

    window.addEventListener('beforeunload', function (e) {
      if (kitgenixSubmittingForm) return;
      if (!document.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-dirty="1"]')) return;
      e.preventDefault();
      e.returnValue = '';
      return '';
    });

    // Plain settings forms (e.g. the General Settings tab) have no tab
    // manager, just native inputs – a generic input/change listener is
    // enough there since nothing mutates those fields via JS directly.
    var plainForms = document.querySelectorAll('#kitgenix-tab-settings form');
    for (var i = 0; i < plainForms.length; i++) {
      (function (form) {
        kitgenixTrackFormSubmit(form);
        form.addEventListener('input', function () { kitgenixMarkFormDirty(form); });
        form.addEventListener('change', function () { kitgenixMarkFormDirty(form); });
      })(plainForms[i]);
    }
  }

  function initTabsManager(root) {
    if (!root) return;

    var modal = null;
    var modalTitle = null;
    var modalIndex = null;
    var titleEl = null;
    var hideTitleEl = null;
    var nicknameEl = null;
    var slugEl = null;
    var priorityEl = null;
    var contentEl = null;
    var saveBtn = null;
    var cancelBtn = null;
    var slugGenBtn = null;
    var visibilityAuthEl = null;
    var visibilityRolesEl = null;
    var visibilityStockEl = null;
    var visibilityPurchasableEl = null;
    var targetSection = null;
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
    var managerType = root.getAttribute('data-kitgenix-custom-tabs-for-woocommerce-manager-type') || 'product';
    var ownerForm = root.closest('form');
    kitgenixTrackFormSubmit(ownerForm);
    function markManagerDirty() { kitgenixMarkFormDirty(ownerForm); }

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
      var targetFieldEl = fields ? fields.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-target-field="1"]') : null;
      return {
        title: getFieldValue(fields, 'title'),
        hideTitle: getFieldValue(fields, 'hide_title'),
        nickname: getFieldValue(fields, 'nickname'),
        slug: getFieldValue(fields, 'slug'),
        priority: getFieldValue(fields, 'priority'),
        content: getFieldContent(fields),
        visibility: getFieldValue(fields, 'visibility'),
        target: targetFieldEl ? targetFieldEl.value : '',
        targetLabels: targetFieldEl ? targetFieldEl.getAttribute('data-kitgenix-custom-tabs-for-woocommerce-target-product-labels') : ''
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
      setFieldValue(fields, 'hide_title', data.hideTitle ? '1' : '');
      setFieldValue(fields, 'nickname', String(data.nickname || '').trim());
      setFieldValue(fields, 'slug', String(data.slug || '').trim());
      setFieldValue(fields, 'priority', priority);
      setFieldContent(fields, String(data.content || ''));
      if (data.visibility) setFieldValue(fields, 'visibility', String(data.visibility));

      if (managerType === 'global' && data.target) {
        var targetFieldEl = fields.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-target-field="1"]');
        if (targetFieldEl) {
          targetFieldEl.value = String(data.target);
          targetFieldEl.setAttribute('data-kitgenix-custom-tabs-for-woocommerce-target-product-labels', String(data.targetLabels || ''));
        }
      }

      syncRowFromFields(index);
      markManagerDirty();
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

    // Reordering swaps stored PRIORITY values (the actual thing that decides
    // render order, since tabs are sorted by priority regardless of array/DOM
    // order) between this row and its current neighbor, then re-sorts the
    // visible table so displayed order always matches what will render.
    function getRowsSortedByPriority() {
      var rows = toArray(tbody.querySelectorAll('[data-kitgenix-custom-tabs-for-woocommerce-row="1"]'));
      return rows.slice().sort(function (a, b) {
        var pa = toInt(getFieldValue(getFields(toInt(a.getAttribute('data-index'), 0)), 'priority'), 0);
        var pb = toInt(getFieldValue(getFields(toInt(b.getAttribute('data-index'), 0)), 'priority'), 0);
        return pa - pb;
      });
    }

    function toArray(nodeList) {
      return Array.prototype.slice.call(nodeList || []);
    }

    function resortTableToPriority() {
      var sorted = getRowsSortedByPriority();
      sorted.forEach(function (row) { tbody.appendChild(row); });
    }

    function moveRow(index, direction) {
      if (index === null) return;
      var fields = getFields(index);
      var row = getRow(index);
      if (!fields || !row) return;

      var sorted = getRowsSortedByPriority();
      var pos = -1;
      for (var i = 0; i < sorted.length; i++) {
        if (toInt(sorted[i].getAttribute('data-index'), null) === index) { pos = i; break; }
      }
      if (pos === -1) return;

      var neighborPos = pos + direction;
      if (neighborPos < 0 || neighborPos >= sorted.length) return; // already at the boundary

      var neighborRow = sorted[neighborPos];
      var neighborIndex = toInt(neighborRow.getAttribute('data-index'), null);
      if (neighborIndex === null) return;
      var neighborFields = getFields(neighborIndex);
      if (!neighborFields) return;

      var thisPriority = getFieldValue(fields, 'priority').trim() || '0';
      var neighborPriority = getFieldValue(neighborFields, 'priority').trim() || '0';

      setFieldValue(fields, 'priority', neighborPriority);
      setFieldValue(neighborFields, 'priority', thisPriority);

      var thisPosNode = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-row-position="1"]');
      var neighborPosNode = neighborRow.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-row-position="1"]');
      if (thisPosNode) thisPosNode.textContent = neighborPriority;
      if (neighborPosNode) neighborPosNode.textContent = thisPriority;

      resortTableToPriority();
      markManagerDirty();
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
          hideTitle: templates[index].hide_title ? '1' : '',
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
      hideTitleEl = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-modal-field="hide_title"]');
      nicknameEl = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-modal-field="nickname"]');
      slugEl = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-modal-field="slug"]');
      priorityEl = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-modal-field="priority"]');
      visibilityAuthEl = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-modal-field="visibility_auth"]');
      visibilityRolesEl = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-modal-field="visibility_roles"]');
      visibilityStockEl = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-modal-field="visibility_stock"]');
      visibilityPurchasableEl = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-modal-field="visibility_purchasable"]');

      // Templates never carry live placement rules; global tabs get both
      // sections, product tabs get visibility only (they're already scoped to
      // one product, so product/category/tag/type targeting is meaningless).
      var visibilitySection = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-visibility-section="1"]');
      targetSection = modal.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-target-section="1"]');
      if (visibilitySection) visibilitySection.hidden = (managerType === 'template');
      if (targetSection) targetSection.hidden = (managerType !== 'global');

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
          if (modal.hidden) return;
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
      hideTitleEl = null;
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
        '<input type="hidden" name="' + base + '[' + index + '][hide_title]" value="" />' +
        '<input type="hidden" name="' + base + '[' + index + '][nickname]" value="" />' +
        '<input type="hidden" name="' + base + '[' + index + '][slug]" value="" />' +
        '<input type="hidden" name="' + base + '[' + index + '][priority]" value="0" />' +
        '<input type="hidden" name="' + base + '[' + index + '][enabled]" value="1" data-kitgenix-custom-tabs-for-woocommerce-enabled-field="1" />' +
        '<input type="hidden" name="' + base + '[' + index + '][visibility]" value="" data-kitgenix-custom-tabs-for-woocommerce-visibility-field="1" />' +
        (managerType === 'global' ? '<input type="hidden" name="' + base + '[' + index + '][target]" value="" data-kitgenix-custom-tabs-for-woocommerce-target-field="1" data-kitgenix-custom-tabs-for-woocommerce-target-product-labels="" />' : '') +
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
        '<td><span class="kitgenix-custom-tabs-for-woocommerce-code" data-kitgenix-custom-tabs-for-woocommerce-row-slug="1">–</span></td>' +
        '<td><span data-kitgenix-custom-tabs-for-woocommerce-row-position="1">0</span></td>' +
        '<td class="kitgenix-custom-tabs-for-woocommerce-actions">' +
        '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-move-up="1" aria-label="' + i18n('moveUp', 'Move up') + '">&#8593;</a> ' +
        '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-move-down="1" aria-label="' + i18n('moveDown', 'Move down') + '">&#8595;</a> ' +
        '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-edit="1">Edit</a> ' +
        '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-duplicate="1">' + i18n('duplicateTab', 'Duplicate') + '</a> ' +
        '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-toggle-enabled="1">' + i18n('disableTab', 'Disable') + '</a> ' +
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
      var slug = getFieldValue(fields, 'slug').trim() || '–';
      var pos = getFieldValue(fields, 'priority').trim() || '0';
      var enabledRaw = getFieldValue(fields, 'enabled');
      var enabled = enabledRaw === '' || enabledRaw === '1';

      var titleNode = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-row-title="1"]');
      var subNode = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-row-subtitle="1"]');
      var slugNode = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-row-slug="1"]');
      var posNode = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-row-position="1"]');
      if (titleNode) titleNode.textContent = displayTitle;
      if (subNode) subNode.textContent = subtitle;
      if (slugNode) slugNode.textContent = slug;
      if (posNode) posNode.textContent = pos;
      ensureRowActions(row);
      syncRowEnabledState(row, enabled);
    }

    function syncRowEnabledState(row, enabled) {
      if (!row) return;
      row.classList.toggle('kitgenix-custom-tabs-for-woocommerce-row-disabled', !enabled);

      var titleCell = row.querySelector('td:first-child');
      var badge = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-row-disabled-badge="1"]');
      if (!enabled) {
        if (!badge && titleCell) {
          badge = document.createElement('span');
          badge.className = 'kitgenix-badge neutral';
          badge.setAttribute('data-kitgenix-custom-tabs-for-woocommerce-row-disabled-badge', '1');
          badge.textContent = i18n('disabledBadge', 'Disabled');
          titleCell.appendChild(badge);
        }
      } else if (badge) {
        badge.remove();
      }

      var toggleBtn = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-toggle-enabled="1"]');
      if (toggleBtn) {
        toggleBtn.textContent = enabled ? i18n('disableTab', 'Disable') : i18n('enableTab', 'Enable');
      }
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

      var emptyState = document.createElement('div');
      emptyState.className = 'kitgenix-empty-state';

      var desc = document.createElement('p');
      desc.className = 'kitgenix-empty-state-desc';
      desc.textContent = String(msg);
      emptyState.appendChild(desc);
      td.appendChild(emptyState);
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

      // Advanced settings (position/nickname/slug/hide title/visibility) start
      // collapsed for a brand-new tab, but open automatically when editing one
      // that already has any of those set, so nothing configured gets hidden.
      try {
        var advanced = modal.querySelector('.kitgenix-custom-tabs-for-woocommerce-advanced');
        if (advanced) advanced.open = (index !== null);
      } catch (_eAdv) {}

      var title = fields ? getFieldValue(fields, 'title') : '';
      var hideTitle = fields ? getFieldValue(fields, 'hide_title') : '';
      var nickname = fields ? getFieldValue(fields, 'nickname') : '';
      var slug = fields ? getFieldValue(fields, 'slug') : '';
      var pos = fields ? getFieldValue(fields, 'priority') : '';
      var content = fields ? getFieldContent(fields) : '';

      titleEl.value = title;
      if (hideTitleEl) hideTitleEl.checked = (hideTitle === '1');
      nicknameEl.value = nickname;
      slugEl.value = slug;

      if (!pos) {
        pos = String(computePriorityForIndex(priorityBase, priorityStep, currentCount()));
      }
      priorityEl.value = String(pos);

      ensureEditor(contentEl);
      ensureVisualEditorReady(contentEl);
      setEditorContent(contentEl, content);

      var visibility = parseJsonObject(fields ? getFieldValue(fields, 'visibility') : '') || {};
      if (visibilityAuthEl) visibilityAuthEl.value = String(visibility.auth || '');
      if (visibilityRolesEl) setMultiSelectValues(visibilityRolesEl, visibility.roles);
      if (visibilityStockEl) visibilityStockEl.value = String(visibility.stock || '');
      if (visibilityPurchasableEl) visibilityPurchasableEl.value = String(visibility.purchasable || '');

      if (targetSection && managerType === 'global') {
        var targetFieldEl = fields ? fields.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-target-field="1"]') : null;
        var target = parseJsonObject(targetFieldEl ? targetFieldEl.value : '') || {};
        var labels = parseJsonObject(targetFieldEl ? targetFieldEl.getAttribute('data-kitgenix-custom-tabs-for-woocommerce-target-product-labels') : '') || {};
        populateTargetSection(target, labels);
      }

      try { titleEl.focus(); } catch (_e) {}
    }

    function parseJsonObject(raw) {
      if (!raw) return null;
      try {
        var parsed = JSON.parse(String(raw));
        return (parsed && typeof parsed === 'object') ? parsed : null;
      } catch (_e) {
        return null;
      }
    }

    function setMultiSelectValues(select, values) {
      if (!select) return;
      var wanted = {};
      (Array.isArray(values) ? values : []).forEach(function (v) { wanted[String(v)] = true; });
      toArray(select.options).forEach(function (opt) {
        opt.selected = !!wanted[String(opt.value)];
      });
    }

    function getMultiSelectValues(select) {
      if (!select) return [];
      return toArray(select.selectedOptions || select.options).filter(function (opt) {
        return opt.selected;
      }).map(function (opt) { return opt.value; });
    }

    function populateTargetSection(target, labels) {
      if (!targetSection) return;
      target = target || {};
      labels = labels || {};

      ['products', 'categories', 'tags', 'types'].forEach(function (dimension) {
        var row = targetSection.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-target-row="' + dimension + '"]');
        if (!row) return;
        var modeEl = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-target-mode="' + dimension + '"]');
        var valuesEl = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-target-values="' + dimension + '"]');
        var dim = target[dimension] || {};
        var exclude = Array.isArray(dim.exclude) ? dim.exclude : [];
        var include = Array.isArray(dim.include) ? dim.include : [];
        var mode = exclude.length ? 'exclude' : 'include';
        var values = exclude.length ? exclude : include;

        if (modeEl) modeEl.value = mode;
        if (!valuesEl) return;

        if ('products' === dimension) {
          // Options for an AJAX-search select don't exist until chosen, so
          // rebuild them from the resolved id->title label map before
          // marking anything selected.
          valuesEl.innerHTML = '';
          values.forEach(function (id) {
            var opt = document.createElement('option');
            opt.value = String(id);
            opt.textContent = String(labels[String(id)] || ('#' + id));
            opt.selected = true;
            valuesEl.appendChild(opt);
          });
          try {
            if (window.jQuery) {
              window.jQuery(valuesEl).trigger('change');
            }
          } catch (_eSelect2) {}
        } else {
          setMultiSelectValues(valuesEl, values);
        }
      });
    }

    function serializeTargetSection() {
      var target = {
        products: { include: [], exclude: [] },
        categories: { include: [], exclude: [] },
        tags: { include: [], exclude: [] },
        types: { include: [], exclude: [] }
      };
      if (!targetSection) return target;

      ['products', 'categories', 'tags', 'types'].forEach(function (dimension) {
        var row = targetSection.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-target-row="' + dimension + '"]');
        if (!row) return;
        var modeEl = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-target-mode="' + dimension + '"]');
        var valuesEl = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-target-values="' + dimension + '"]');
        var mode = modeEl && modeEl.value === 'exclude' ? 'exclude' : 'include';
        var values = getMultiSelectValues(valuesEl);
        target[dimension][mode] = values;
      });

      return target;
    }

    function collectTargetProductLabels() {
      var labels = {};
      if (!targetSection) return labels;
      var row = targetSection.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-target-row="products"]');
      var valuesEl = row ? row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-target-values="products"]') : null;
      if (!valuesEl) return labels;
      toArray(valuesEl.options).forEach(function (opt) {
        labels[String(opt.value)] = String(opt.textContent || opt.value);
      });
      return labels;
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
      setFieldValue(fields, 'hide_title', (hideTitleEl && hideTitleEl.checked) ? '1' : '');
      setFieldValue(fields, 'nickname', nickname);
      setFieldValue(fields, 'slug', slug);
      setFieldValue(fields, 'priority', pos);
      setFieldContent(fields, content);

      var visibility = {
        auth: visibilityAuthEl ? String(visibilityAuthEl.value || '') : '',
        roles: visibilityRolesEl ? getMultiSelectValues(visibilityRolesEl) : [],
        stock: visibilityStockEl ? String(visibilityStockEl.value || '') : '',
        purchasable: visibilityPurchasableEl ? String(visibilityPurchasableEl.value || '') : ''
      };
      setFieldValue(fields, 'visibility', JSON.stringify(visibility));

      if (targetSection && managerType === 'global') {
        var targetFieldEl = fields.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-target-field="1"]');
        if (targetFieldEl) {
          targetFieldEl.value = JSON.stringify(serializeTargetSection());
          targetFieldEl.setAttribute('data-kitgenix-custom-tabs-for-woocommerce-target-product-labels', JSON.stringify(collectTargetProductLabels()));
        }
      }

      syncRowFromFields(index);
      markManagerDirty();

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

      var moveUp = t.closest('[data-kitgenix-custom-tabs-for-woocommerce-move-up="1"]');
      if (moveUp) {
        e.preventDefault();
        var rowUp = moveUp.closest('[data-kitgenix-custom-tabs-for-woocommerce-row="1"]');
        if (!rowUp) return;
        moveRow(toInt(rowUp.getAttribute('data-index'), null), -1);
        try { moveUp.focus(); } catch (_eFocus1) {}
        return;
      }

      var moveDown = t.closest('[data-kitgenix-custom-tabs-for-woocommerce-move-down="1"]');
      if (moveDown) {
        e.preventDefault();
        var rowDown = moveDown.closest('[data-kitgenix-custom-tabs-for-woocommerce-row="1"]');
        if (!rowDown) return;
        moveRow(toInt(rowDown.getAttribute('data-index'), null), 1);
        try { moveDown.focus(); } catch (_eFocus2) {}
        return;
      }

      var toggleEnabled = t.closest('[data-kitgenix-custom-tabs-for-woocommerce-toggle-enabled="1"]');
      if (toggleEnabled) {
        e.preventDefault();
        var rowToggle = toggleEnabled.closest('[data-kitgenix-custom-tabs-for-woocommerce-row="1"]');
        if (!rowToggle) return;
        var idxToggle = toInt(rowToggle.getAttribute('data-index'), null);
        if (idxToggle === null) return;
        var fieldsToggle = getFields(idxToggle);
        if (!fieldsToggle) return;
        var nowEnabled = getFieldValue(fieldsToggle, 'enabled') !== '1';
        setFieldValue(fieldsToggle, 'enabled', nowEnabled ? '1' : '0');
        syncRowEnabledState(rowToggle, nowEnabled);
        markManagerDirty();
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
        markManagerDirty();
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

  /* ------------------------------------------------------------------
     Click-to-sort table headers (Global Tabs / Templates management tables)
     Purely a display reorder of the visible <tr> rows – it never touches the
     hidden field values or their own container's order, so it can't affect
     what gets saved (each row's actual render order is decided by its own
     stored `priority` value, not array/DOM position). No manager-dirty marking.
  ------------------------------------------------------------------ */
  function initSortableTables() {
    var tables = document.querySelectorAll('[data-kitgenix-sortable-table="1"]');
    for (var i = 0; i < tables.length; i++) {
      bindSortableTable(tables[i]);
    }
  }

  function bindSortableTable(table) {
    if (!table || table._kitgenixSortBound) return;
    table._kitgenixSortBound = true;

    var tbody = table.querySelector('tbody');
    if (!tbody) return;

    var headers = toArray(table.querySelectorAll('th[data-kitgenix-sort-key]'));
    var state = { key: null, dir: 1 };

    function cellValue(row, key) {
      if (key === 'priority') {
        var posEl = row.querySelector('[data-kitgenix-custom-tabs-for-woocommerce-row-position="1"]');
        return toInt(posEl ? posEl.textContent : '0', 0);
      }
      var selector = key === 'title'
        ? '[data-kitgenix-custom-tabs-for-woocommerce-row-title="1"]'
        : '[data-kitgenix-custom-tabs-for-woocommerce-row-slug="1"]';
      var el = row.querySelector(selector);
      return (el ? el.textContent : '').toLowerCase();
    }

    headers.forEach(function (th) {
      th.setAttribute('tabindex', '0');
      th.setAttribute('role', 'button');
      th.setAttribute('aria-sort', 'none');

      function activate() {
        var key = th.getAttribute('data-kitgenix-sort-key');
        state.dir = (state.key === key) ? -state.dir : 1;
        state.key = key;

        headers.forEach(function (h) {
          h.classList.remove('kitgenix-sort-asc', 'kitgenix-sort-desc');
          if (h === th) {
            h.classList.add(state.dir === 1 ? 'kitgenix-sort-asc' : 'kitgenix-sort-desc');
            h.setAttribute('aria-sort', state.dir === 1 ? 'ascending' : 'descending');
          } else {
            h.setAttribute('aria-sort', 'none');
          }
        });

        var rows = toArray(tbody.querySelectorAll('[data-kitgenix-custom-tabs-for-woocommerce-row="1"]'));
        rows.sort(function (a, b) {
          var va = cellValue(a, key);
          var vb = cellValue(b, key);
          if (va < vb) return -1 * state.dir;
          if (va > vb) return 1 * state.dir;
          return 0;
        });
        rows.forEach(function (row) { tbody.appendChild(row); });
      }

      th.addEventListener('click', activate);
      th.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          activate();
        }
      });
    });
  }

  function initNoticeDismiss() {
    var closers = document.querySelectorAll('.kitgenix-notice-close');
    for (var i = 0; i < closers.length; i++) {
      closers[i].addEventListener('click', function (e) {
        var notice = e.currentTarget.closest('.kitgenix-notice');
        if (notice) notice.remove();
      });
    }
  }

  /* ------------------------------------------------------------------
     Portability: JSON import preview
     Reads the chosen file client-side (it never leaves the browser until
     the admin actually submits) and shows a short summary of what it
     contains before Replace/Merge is committed server-side. The existing
     server-side validation/sanitization in Portability::handle_import_json()
     is unchanged and remains authoritative – this is a preview only.
  ------------------------------------------------------------------ */
  function initImportPreview() {
    var fileInput = document.getElementById('kitgenix_ctw_import_json_file');
    var preview = document.getElementById('kitgenix-ctw-import-json-preview');
    var submitBtn = document.getElementById('kitgenix-ctw-import-json-submit');
    if (!fileInput || !preview) return;

    if (submitBtn) submitBtn.disabled = true;

    function escapeHtml(s) {
      return String(s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }

    function showError(message) {
      preview.hidden = false;
      preview.innerHTML = '<div class="kitgenix-notice kitgenix-notice-error"><div class="kitgenix-notice-body"><p class="kitgenix-notice-text"></p></div></div>';
      preview.querySelector('.kitgenix-notice-text').textContent = message;
      if (submitBtn) submitBtn.disabled = true;
    }

    function showPreview(data) {
      var pluginId = (data && typeof data.plugin === 'string') ? data.plugin : '';
      var expectedId = 'kitgenix-custom-tabs-for-woocommerce';
      var settings = (data && typeof data.settings === 'object' && data.settings) ? data.settings : {};
      var globalTabs = Array.isArray(data && data.global_tabs) ? data.global_tabs : [];
      var templates = Array.isArray(data && data.templates) ? data.templates : [];

      var html = '<div class="kitgenix-notice ' + (pluginId && pluginId !== expectedId ? 'kitgenix-notice-warning' : 'kitgenix-notice-info') + '">';
      html += '<div class="kitgenix-notice-body"><p class="kitgenix-notice-title">Import preview</p>';
      if (pluginId && pluginId !== expectedId) {
        html += '<p class="kitgenix-notice-text">This file was exported from a different plugin/slug (<code>' + escapeHtml(pluginId) + '</code>). The import will likely be rejected on submit.</p>';
      }
      html += '<ul style="margin:6px 0 0 18px;list-style:disc;">';
      html += '<li>' + globalTabs.length + ' global tab(s)</li>';
      html += '<li>' + templates.length + ' template(s)</li>';
      html += '<li>' + Object.keys(settings).length + ' setting key(s)</li>';
      if (data && data.exported_at) html += '<li>Exported ' + escapeHtml(String(data.exported_at)) + '</li>';
      html += '</ul></div></div>';

      preview.hidden = false;
      preview.innerHTML = html;
      if (submitBtn) submitBtn.disabled = false;
    }

    fileInput.addEventListener('change', function () {
      var file = this.files && this.files[0];
      if (!file) {
        preview.hidden = true;
        preview.innerHTML = '';
        if (submitBtn) submitBtn.disabled = true;
        return;
      }
      if (!window.FileReader) {
        if (submitBtn) submitBtn.disabled = false;
        return;
      }
      var reader = new FileReader();
      reader.onload = function () {
        try {
          showPreview(JSON.parse(String(reader.result || '')));
        } catch (e) {
          showError('This file is not valid JSON, so it cannot be imported.');
        }
      };
      reader.onerror = function () { showError('Could not read this file.'); };
      reader.readAsText(file);
    });
  }

  function boot() {
    var roots = document.querySelectorAll('[data-kitgenix-custom-tabs-for-woocommerce-manager="1"]');
    for (var i = 0; i < roots.length; i++) initTabsManager(roots[i]);
    initPositionPresetSync();
    initNoticeDismiss();
    initImportPreview();
    initSortableTables();
    initUnsavedChangesGuard();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();

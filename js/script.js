((Drupal, once, drupalSettings) => {
  function updateOffCanvasDimensions() {
    const wrapper = document.getElementById('drupal-off-canvas-wrapper');
    if (wrapper) {
      wrapper.style.position = 'fixed';
      wrapper.style.top = '0';
      wrapper.style.left = 'auto';
      wrapper.style.right = '0';
      wrapper.style.bottom = '0';
      wrapper.style.margin = '0';
      wrapper.style.transform = 'none';
      wrapper.style.zIndex = '9999';
      wrapper.style.height = `${window.innerHeight}px`;
      wrapper.style.overflowY = 'auto';
      wrapper.style.removeProperty('width');
    }

    const dialog = wrapper?.closest('.ui-dialog');
    if (dialog) {
      dialog.style.position = 'fixed';
      dialog.style.top = '0';
      dialog.style.right = '0';
      dialog.style.left = 'auto';
      dialog.style.margin = '0';
      dialog.style.transform = 'none';
      dialog.style.zIndex = '9999';
      dialog.style.height = `${window.innerHeight}px`;
      dialog.style.overflowY = 'auto';
      dialog.style.width = 'auto';
    }
  }

  Drupal.behaviors.helpGuideCombined = {
    attach(context) {
      // --- Move Help Guide Links ---
      if (once('help-guide-move', 'html', context).length) {
        window.addEventListener('load', () => {
          if (
            drupalSettings.pu_help_guide &&
            typeof drupalSettings.pu_help_guide.fields === 'object'
          ) {
            Object.entries(drupalSettings.pu_help_guide.fields).forEach(([fieldName, helpGuideId]) => {
              const wrapperId = `edit-${fieldName.replace(/_/g, "-")}-wrapper`;
              const wrapper = document.getElementById(wrapperId);
              console.log(`Wrapper: ${wrapperId}`);

              if (!wrapper) {
                console.warn(`Wrapper not found: ${wrapperId}`);
                return;
              }

              // Create the off-canvas modal link
              const link = document.createElement('a');
              link.href = '/help-guide/' + helpGuideId;
              link.className = 'help-guide-modal-button bg-icon use-ajax';
              link.setAttribute('data-dialog-type', 'dialog');
              link.setAttribute('data-dialog-renderer', 'off_canvas');
              link.setAttribute('data-dialog-options', JSON.stringify({ width: 400 }));

              const label = wrapper.querySelector("label");
              const summary = wrapper.querySelector("summary");
              const fieldset = wrapper.querySelector("fieldset");
              const legendSpan = fieldset?.querySelector("legend span");
              const tableHeader = wrapper.querySelector("table thead h4");

              if (tableHeader) {
                tableHeader.appendChild(link);
              } else if (legendSpan) {
                const newSpan = document.createElement('span');
                newSpan.innerHTML = legendSpan.innerHTML;
                legendSpan.replaceChildren(newSpan);
                newSpan.insertAdjacentElement("afterend", link);
              } else if (label) {
                label.appendChild(link);
              } else if (summary) {
                summary.appendChild(link);
              } else {
                console.warn(`No valid placement found for: ${fieldName}`);
              }
              // Important: Ensure Drupal behaviors (like use-ajax) are attached
              Drupal.attachBehaviors(link);
            });
          }
        });
      }

      // --- Disable Off-Canvas Draggable BEFORE creation ---
      document.addEventListener('dialog:beforecreate', (event) => {
        if (event.detail?.settings) {
          event.detail.settings.draggable = false;
        }
      });

      document.addEventListener('dialog:aftercreate', () => {
        document.body.style.overflow = 'auto';

        setTimeout(() => {
          const wrapper = document.getElementById('drupal-off-canvas-wrapper');
          const dialog = wrapper?.closest('.ui-dialog');

          if (wrapper) {
            wrapper.scrollTop = 0;
            wrapper.style.position = 'fixed';
            wrapper.style.top = '0';
            wrapper.style.left = 'auto';
            wrapper.style.right = '0';
            wrapper.style.bottom = '0';
            wrapper.style.margin = '0';
            wrapper.style.transform = 'none';
            wrapper.style.zIndex = '9999';
            wrapper.style.height = `${window.innerHeight}px`;
            wrapper.style.overflowY = 'auto';
            wrapper.style.width = 'auto';
            wrapper.classList.remove('ui-draggable');
            wrapper.removeAttribute('draggable');
          }

          if (dialog) {
            dialog.classList.remove('ui-draggable');
            dialog.removeAttribute('draggable');

            const titlebar = dialog.querySelector('.ui-dialog-titlebar');
            if (titlebar) {
              titlebar.onmousedown = null;
              titlebar.onmousemove = null;
              titlebar.onmouseup = null;

              const titleTextEl = titlebar.querySelector('.ui-dialog-title');
              if (titleTextEl && !titleTextEl.dataset.isLinked) {
                var dialogBtnEle = document.getElementsByClassName('help-guide-modal-button')[0];
                var currentTitle = titleTextEl.innerHTML;
                if (dialogBtnEle) {
                  var clickedDialogUrl = dialogBtnEle.getAttribute('href');
                  currentTitle = `<a href="${clickedDialogUrl}" target="_blank">${titleTextEl.innerHTML}</a>`;
                }
                titleTextEl.innerHTML = currentTitle;
                titleTextEl.dataset.isLinked = 'true';
              }

              if (window.jQuery && window.jQuery.ui?.draggable) {
                if (window.jQuery(dialog).data('ui-draggable')) {
                  window.jQuery(dialog).draggable('destroy');
                }
                window.jQuery(dialog).off();
                window.jQuery(titlebar).off();
              }
            }
          }
        }, 10);
      });

      document.addEventListener('dialog:afterclose', () => {
        document.body.style.overflow = '';
      });

      window.addEventListener('resize', updateOffCanvasDimensions);
      updateOffCanvasDimensions();

      // --- Align Form Action Buttons Left ---
      once('help-guide-align-buttons', '#edit-actions.form-actions', context).forEach((element) => {
        element.style.marginLeft = '0';
        element.style.paddingLeft = '0';
      });

      window.addEventListener('resize', () => {
        document.querySelectorAll('#edit-actions.form-actions').forEach((element) => {
          element.style.marginLeft = '0';
          element.style.paddingLeft = '0';
        });
      });
    }
  };
})(Drupal, once, drupalSettings);

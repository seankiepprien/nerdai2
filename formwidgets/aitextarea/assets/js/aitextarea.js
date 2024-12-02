/*
 * This is a sample JavaScript file used by AITextArea
 *
 * You can delete this file if you want
 */
addEventListener('ajax:promise', function(event) {
    event.target.closest('form').querySelectorAll('button').forEach(function(el) {
        el.display = true;
    });
});

addEventListener('ajax:always', function() {
    event.target.closest('form').querySelectorAll('button').forEach(function(el) {
        el.display = false;
    });
});

function updatePreview(htmlContent) {
    document.getElementById('aiResponse').value = htmlContent;

    const iframe = document.getElementById('AIHtmlPreview');
    const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;

    iframeDocument.open();
    iframeDocument.write(htmlContent);
    iframeDocument.close();
}

document.addEventListener('DOMContentLoaded', function() {
    oc.richEditorRegisterButton('insertAdvancePrompt', {
        title: 'NerdAI Advanced Prompt',
        icon: '<i class="icon-magic"></i>',
        undo: true,
        focus: true,
        refreshOnCallback: true,
        callback: function () {
            $.popup({
                handler: 'onLoadPopup',
                size: 'large',                  // Set popup size (small, large, etc.)
                keyboard: true,                 // Allow closing with keyboard
                extraData: {                    // Additional data to send
                    param1: 'value1',
                    param2: 'value2'
                }
            });

            addEventListener('ajax:request-success', function(event) {
                const handler = event.detail.context.handler;
                if (handler === 'onAddAIToEditor') {
                    const editor = $('#RichEditor-formTestRicheditor-test_richeditor'); // Update the selector
                    const aiText = event.detail.data.result;

                    if (editor.length > 0) {
                        editor.richEditor('insertHtml', '')
                        editor.richEditor('insertHtml', aiText);
                        updatePreview();
                    } else {
                        console.error('Editor not found.');
                    }
                }
            });
        }
    });

    oc.richEditorButtons.splice(0, 0, 'insertAdvancePrompt');
});


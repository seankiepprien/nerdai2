<div data-control="toolbar">
    <a
        href="<?= Backend::url('nerd/nerdai/assistants/create') ?>"
        class="btn btn-primary oc-icon-plus">
        New Assistant
    </a>

    <button
        class="btn btn-default oc-icon-refresh"
        data-request="onImportAssistants"
        data-request-confirm="Are you sure you want to import assistants from OpenAI API?"
        data-load-indicator="Importing assistants...">
        Import Assistants
    </button>

    <button
        class="btn btn-danger oc-icon-trash-o"
        disabled="disabled"
        onclick="$(this).data('request-data', { checked: $('.control-list').listWidget('getChecked') })"
        data-request="onDelete"
        data-request-confirm="Are you sure you want to delete the selected assistants?"
        data-trigger-action="enable"
        data-trigger=".control-list input[type=checkbox]"
        data-trigger-condition="checked"
        data-request-success="$(this).prop('disabled', true)"
        data-stripe-load-indicator>
        Delete selected
    </button>
</div>

<?php namespace Nerd\Nerdai\Controllers;

use Backend;
use BackendMenu;
use Backend\Classes\Controller;
use Nerd\Nerdai\Classes\Services\AssistantService;
use Nerd\Nerdai\Models\Assistant;
use Nerd\nerdai\Models\Thread;
use Flash;
use Exception;

/**
 * Assistants Backend Controller
 *
 * @link https://docs.octobercms.com/3.x/extend/system/controllers.html
 */
class Assistants extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];

    /**
     * @var string formConfig file
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var string listConfig file
     */
    public $listConfig = 'config_list.yaml';

    /**
     * @var array required permissions
     */
    public $requiredPermissions = ['nerd.nerdai.assistants'];

    protected $assistantService;

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Nerd.Nerdai', 'nerdai', 'assistants');

        $this->assistantService = new AssistantService();
    }

    public function create()
    {
        $this->pageTitle = 'Create Assistant';
        return $this->asExtension('FormController')->create();
    }

    public function onCreate()
    {
        try {
            $data = post();

            $assistantData = $data['Assistant'] ?? [];

            $tools = [];
            if (!empty($assistantData['enable_code_interpreter'])) {
                $tools[] = ['type' => 'code_interpreter'];
            }

            if (!empty($assistantData['enable_retrieval'])) {
                $tools[] = ['type' => 'retrieval'];
            }

            if (!empty($data['enable_function_calling']) && !empty($data['function_schemas'])) {
                $functionSchemas = json_decode($assistantData['function_schemas'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new EXCEPTION('Invalid function schemas format');
                }

                foreach ($functionSchemas as $schema) {
                    $tools[] = [
                        'type' => 'function',
                        'function' => $schema
                    ];
                }
            }

            $assistant = $this->assistantService->createAssistant(
                $assistantData['name'],
                $assistantData['instructions'],
                $assistantData['description'] ?? '',
                $tools,
                ['model' => $assistantData['model']]
            );

            Flash::success('Assistant created successfully');
            return redirect()->to(Backend::url('nerd/nerdai/assistants'));
        } catch (Exception $e) {
            Flash::error($e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function update($recordId = null)
    {
        $this->pageTitle = 'Edit Assistant';

        // Check if recordId is valid
        if (!$recordId) {
            Flash::error('No assistant ID was provided');
            return redirect()->to(Backend::url('nerd/nerdai/assistants'));
        }

        // Ensure the record exists before attempting to update
        $assistant = Assistant::find($recordId);
        if (!$assistant) {
            Flash::error('Assistant not found');
            return redirect()->to(Backend::url('nerd/nerdai/assistants'));
        }

        $this->asExtension('FormController')->update($recordId);
    }

    public function onUpdate()
    {
        try {
            $data = post();

            $assistantData = $data['Assistant'] ?? [];


            $id = $assistantData['id'] ?? null;


            if (!$id) {
                throw new Exception('Assistant ID is required');
            }

            $tools = [];
            if (!empty($assistantData['enable_code_interpreter'])) {
                $tools[] = ['type' => 'code_interpreter'];
            }

            if (!empty($assistantData['enable_retrieval'])) {
                $tools[] = ['type' => 'retrieval'];
            }

            if (!empty($assistantData['enable_function_calling']) && !empty($assistantData['function_schemas'])) {
                $functionSchemas = json_decode($assistantData['function_schemas'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new EXCEPTION('Invalid function schemas format');
                }

                foreach ($functionSchemas as $schema) {
                    $tools[] = [
                        'type' => 'function',
                        'function' => $schema
                    ];
                }
            }

            $updateData = [
                'name' => $assistantData['name'],
                'instructions' => $assistantData['instructions'],
                'description' => $assistantData['description'],
                'tools' => $tools,
                'is_active' => !empty($assistantData['is_active']),
            ];

            if (!empty($assistantData['model'])) {
                $updateData['model'] = $assistantData['model'];
            }

            $assistant = $this->assistantService->updateAssistant($id, $updateData);

            Flash::success('Assistant updated successfully');
            return redirect()->to(Backend::url('nerd/nerdai/assistants'));
        } catch (Exception $e) {
            Flash::error($e->getMessage());
            return redirect()->back();
        }
    }

    public function threads($assistantId)
    {
        $this->pageTitle = 'Threads';
        $assistant = Assistant::findOrFail($assistantId);
        $threads = $this->assistantService->getThreads($assistantId);

        $this->vars['assistant'] = $assistant;
        $this->vars['threads'] = $threads;

        return $this->makeView('threads');
    }

    public function onCreateThread()
    {
        try {
            $data = post();
            $assistantId = $data['assistant_id'] ?? null;

            if (!$assistantId) {
                throw new Exception('assistantID is required');
            }

            $title = $data['title'] ?? '';
            $description = $data['description'] ?? '';

            $thread = $this->assistantService->createThread(
                $assistantId,
                $title,
                $description
            );

            Flash::success('Thread created successfully');

            return [
                'thread_id' => $thread->id,
                'redirect' => Backend::url('nerd/nerdai/assistants/messages/' . $thread->id)
            ];
        } catch (Exception $e) {
            Flash::error($e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    public function messages($threadId)
    {
        $this->pageTitle = 'Conversation';
        $thread = Thread::with('assistant')->findOrFail($threadId);
        $messages = $this->assistantService->getMessages($threadId, 100, 'asc');

        $this->vars['thread'] = $thread;
        $this->vars['assistant'] = $thread->assistant;
        $this->vars['messages'] = $messages;

        return $this->makeView('messages');
    }

    public function onSendMessage()
    {
        try {
            $data = post();
            $threadId = $data['thread_id'] ?? null;
            $content = $data['content'] ?? null;

            if (!$threadId || !$content) {
                throw new Exception('Thread ID and content are required');
            }

            $response = $this->assistantsService->sendMessage($threadId, $content);

            return [
                'success' => true,
                'message' => $response['message'],
                'message_id' => $response['message_id']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function onDelete()
    {
        try {
            $data = post();

            \Log::notice($data);

            $assistantData = $data['Assistant'] ?? [];

            $id = $assistantData['id'] ?? null;

            if (!$id) {
                throw new Exception('Assistant ID is required');
            }

            $assistant = Assistant::findOrFail($id);

            $this->assistantService->deleteAssistant($assistant->assistant_id);

            $assistant->delete();

            Flash::success('Assistant deleted successfully');
            return [
                'redirect' => Backend::url('nerd/nerdai/assistants')
            ];
        } catch (Exception $e) {
            Flash::error($e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    public function onImportAssistants()
    {
        try {
            $imported = $this->assistantService->importAssistants();

            Flash::success('Successfully imported {$imported} assistants from OpenAI');
            return redirect()->refresh();
        } catch (Exception $e) {
            Flash::error($e->getMessage());
            return redirect()->back();
        }
    }
}

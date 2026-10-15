<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Controller\Adminhtml;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Registry;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Abstract admin CRUD controller that works through a repository.
 *
 * Child controller only needs to define ADMIN_RESOURCE and receive a repository via di.xml:
 *
 * <type name="Magefan\Blog\Controller\Adminhtml\Post">
 *     <arguments>
 *         <argument name="repository" xsi:type="object">Magefan\Blog\Api\PostRepositoryInterface</argument>
 *     </arguments>
 * </type>
 *
 * Repository contract: getById($id), save($model), delete($model), getList($searchCriteria)
 * and getFactory() (used to create a new model; override createModel() if the repository has no factory).
 */
abstract class ActionsV2 extends Action
{
    /**
     * Form data persistor key. Null means "{route}_{controller}_form_data", e.g. "blog_post_form_data".
     */
    public const FORM_SESSION_KEY = null;

    /**
     * Admin menu item id. Null means static::ADMIN_RESOURCE.
     */
    public const ACTIVE_MENU = null;

    /**
     * Model field changed by massStatus action.
     */
    public const STATUS_FIELD = 'is_active';

    /**
     * Request param that holds entity id(s).
     */
    public const ID_KEY = 'id';

    /**
     * Request param that holds form data. Null means form data is the whole request.
     */
    public const PARAMS_HOLDER = null;

    /**
     * System configuration section id used by config action.
     */
    public const CONFIG_SECTION = null;

    /**
     * Actions that can be handled by this controller.
     */
    public const PREPARED_ACTIONS = [
        'index', 'grid', 'new', 'edit', 'save', 'duplicate', 'delete', 'config', 'massStatus'
    ];

    /**
     * Entity repository, injected via di.xml.
     *
     * @var object|null
     */
    protected $repository;

    /**
     * @var AbstractModel|null
     */
    protected $model;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param Registry $registry
     * @param Filter $filter
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param mixed $repository Repository object or null. Declared as "mixed" for setup:di:compile compatibility.
     */
    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        Registry $registry,
        Filter $filter,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        $repository = null
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->registry = $registry;
        $this->filter = $filter;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->repository = $repository;
        parent::__construct($context);
    }

    /**
     * Dispatch request to the matching "{action}Action" method.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $action = $this->getRequest()->getActionName();
        if (!in_array($action, static::PREPARED_ACTIONS, true)) {
            return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
        }

        $method = $action . 'Action';
        $this->beforeAction();
        $result = $this->$method();
        $this->afterAction();

        return $result;
    }

    /**
     * Retrieve entity repository.
     *
     * @return object
     * @throws LocalizedException
     */
    protected function getRepository()
    {
        if (null === $this->repository) {
            throw new LocalizedException(
                __('Repository is not specified for %1. Please define it in di.xml.', static::class)
            );
        }
        return $this->repository;
    }

    /**
     * Create new empty model.
     *
     * @return AbstractModel
     * @throws LocalizedException
     */
    protected function createModel()
    {
        $repository = $this->getRepository();
        if (!method_exists($repository, 'getFactory')) {
            throw new LocalizedException(
                __('%1 has no getFactory() method. Please override createModel().', get_class($repository))
            );
        }
        return $repository->getFactory()->create();
    }

    /**
     * Retrieve form data persistor key.
     *
     * @return string
     */
    protected function getFormSessionKey()
    {
        if (null !== static::FORM_SESSION_KEY) {
            return static::FORM_SESSION_KEY;
        }
        $request = $this->getRequest();
        return $request->getModuleName() . '_' . $request->getControllerName() . '_form_data';
    }

    /**
     * Retrieve active admin menu item id.
     *
     * @return string
     */
    protected function getActiveMenu()
    {
        return null !== static::ACTIVE_MENU ? static::ACTIVE_MENU : static::ADMIN_RESOURCE;
    }

    /**
     * Retrieve status field name used by massStatus action.
     *
     * @return string|null
     */
    protected function getStatusField()
    {
        return static::STATUS_FIELD;
    }

    /**
     * Retrieve entity title, e.g. "Post" or "Posts".
     *
     * @param bool $plural
     * @return string
     */
    protected function getEntityTitle($plural = false)
    {
        try {
            $model = $this->model ?: $this->createModel();
        } catch (Exception $e) {
            $model = null;
        }

        if ($model && method_exists($model, 'getOwnTitle')) {
            return (string)$model->getOwnTitle($plural);
        }
        return (string)($plural ? __('Items') : __('Item'));
    }

    /**
     * Index action
     *
     * @return ResultInterface
     */
    protected function indexAction()
    {
        if ($this->getRequest()->getParam('ajax')) {
            return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('grid');
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $this->setActiveMenuOnPage($resultPage);

        $title = __('Manage %1', $this->getEntityTitle(true));
        $resultPage->getConfig()->getTitle()->prepend($title);
        $resultPage->addBreadcrumb($title, $title);

        return $resultPage;
    }

    /**
     * Grid action
     *
     * @return ResultInterface
     */
    protected function gridAction()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
    }

    /**
     * New action
     *
     * @return ResultInterface
     */
    protected function newAction()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('edit');
    }

    /**
     * Edit action
     *
     * @return ResultInterface
     */
    protected function editAction()
    {
        try {
            $model = $this->getModel();
            $this->registry->register('current_model', $model);

            /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
            $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $this->setActiveMenuOnPage($resultPage);

            $title = $this->getEntityTitle();
            if ($model->getId()) {
                $breadcrumbTitle = __('Edit %1', $title);
                $breadcrumbLabel = $breadcrumbTitle;
            } else {
                $breadcrumbTitle = __('New %1', $title);
                $breadcrumbLabel = __('Create %1', $title);
            }
            $resultPage->getConfig()->getTitle()->prepend(__($title));
            $resultPage->getConfig()->getTitle()->prepend(
                $model->getId() ? $this->getModelName($model) : __('New %1', $title)
            );
            $resultPage->addBreadcrumb($breadcrumbLabel, $breadcrumbTitle);

            // restore data
            $values = $this->dataPersistor->get($this->getFormSessionKey());
            if (static::PARAMS_HOLDER && is_array($values)) {
                $values = isset($values[static::PARAMS_HOLDER]) ? $values[static::PARAMS_HOLDER] : null;
            }
            if ($values) {
                $model->addData($values);
            }

            return $resultPage;
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Item is not longer exist.'));
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong: %1', $e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }

    /**
     * Retrieve model name for page title.
     *
     * @param AbstractModel $model
     * @return string
     */
    protected function getModelName(AbstractModel $model)
    {
        return (string)($model->getName() ?: $model->getTitle());
    }

    /**
     * Save action
     *
     * @return ResultInterface
     */
    protected function saveAction()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->resultRedirectFactory->create()->setPath('*/*');
        }

        $params = [];
        $model = null;
        $eventPrefix = $this->getEventPrefix();

        try {
            $model = $this->getModel();

            $params = static::PARAMS_HOLDER ? $request->getParam(static::PARAMS_HOLDER) : $request->getParams();
            $params = $this->filterParams($params);

            $idFieldName = $model->getIdFieldName();
            if (isset($params[$idFieldName]) && empty($params[$idFieldName])) {
                unset($params[$idFieldName]);
            }
            $model->addData($params);

            $this->_eventManager->dispatch($eventPrefix . '_form_before_save', ['model' => $model]);
            $this->beforeSave($model, $request);

            $savedModel = $this->getRepository()->save($model);
            if ($savedModel instanceof AbstractModel) {
                $model = $savedModel;
                $this->model = $model;
            }

            $this->afterSave($model, $request);
            $this->_eventManager->dispatch($eventPrefix . '_form_after_save', ['model' => $model]);

            $this->messageManager->addSuccessMessage(__('%1 has been saved.', $this->getEntityTitle()));
            $this->setFormData(false);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Item is not longer exist.'));
            return $this->resultRedirectFactory->create()->setPath('*/*');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage(nl2br($e->getMessage()));
            $this->setFormData($params);
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __(
                    'Something went wrong while saving this %1. %2',
                    strtolower($this->getEntityTitle()),
                    $e->getMessage()
                )
            );
            $this->setFormData($params);
        }

        $hasError = (bool)$this->messageManager->getMessages()->getCountByType(MessageInterface::TYPE_ERROR);
        $modelId = $model ? $model->getId() : null;

        if ($request->getParam('isAjax')) {
            $block = $this->_view->getLayout()->getMessagesBlock();
            $block->setMessages($this->messageManager->getMessages(true));

            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
                'messages' => $block->getGroupedHtml(),
                'error' => $hasError,
                'model' => $model ? $model->toArray() : [],
            ]);
        }

        $routeParams = [];
        if ($storeId = (int)$request->getParam('store')) {
            $routeParams['store'] = $storeId;
        }

        if ($hasError || $request->getParam('back')) {
            $routeParams[static::ID_KEY] = $modelId;
            return $this->resultRedirectFactory->create()->setPath('*/*/edit', $routeParams);
        }

        return $this->resultRedirectFactory->create()->setPath('*/*', $routeParams);
    }

    /**
     * Duplicate action
     *
     * @return ResultInterface
     */
    protected function duplicateAction()
    {
        $originId = (int)$this->getRequest()->getParam(static::ID_KEY);
        try {
            $originModel = $this->getModel();
            if (!$originModel->getId()) {
                throw new NoSuchEntityException();
            }

            $model = $this->duplicateModel($originModel);

            $this->messageManager->addSuccessMessage(__('%1 has been duplicated.', $this->getEntityTitle()));
            return $this->resultRedirectFactory->create()->setPath('*/*/edit', [static::ID_KEY => $model->getId()]);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Item is not longer exist.'));
            return $this->resultRedirectFactory->create()->setPath('*/*');
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __(
                    'Something went wrong while saving this %1. %2',
                    strtolower($this->getEntityTitle()),
                    $e->getMessage()
                )
            );
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/edit', [static::ID_KEY => $originId]);
    }

    /**
     * Create and save a copy of the model. Override to reset entity specific fields.
     *
     * @param AbstractModel $originModel
     * @return AbstractModel
     * @throws LocalizedException
     */
    protected function duplicateModel(AbstractModel $originModel)
    {
        $model = clone $originModel;
        $model->unsetData($model->getIdFieldName());
        $model->isObjectNew(true);

        $savedModel = $this->getRepository()->save($model);
        return $savedModel instanceof AbstractModel ? $savedModel : $model;
    }

    /**
     * Delete action
     *
     * @return ResultInterface
     */
    protected function deleteAction()
    {
        $count = 0;
        $error = false;
        try {
            $repository = $this->getRepository();
            foreach ($this->getMassActionModels() as $item) {
                $repository->delete($item);
                $count++;
            }
        } catch (LocalizedException $e) {
            $error = true;
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Exception $e) {
            $error = true;
            $this->messageManager->addExceptionMessage(
                $e,
                __(
                    "We can't delete %1 right now. %2",
                    strtolower($this->getEntityTitle()),
                    $e->getMessage()
                )
            );
        }

        if (!$error) {
            $this->messageManager->addSuccessMessage(
                __('%1 have been deleted.', $this->getEntityTitle($count > 1))
            );
        }

        return $this->resultRedirectFactory->create()->setPath('*/*');
    }

    /**
     * Change status action
     *
     * @return ResultInterface
     */
    protected function massStatusAction()
    {
        $count = 0;
        $error = false;
        try {
            $status = $this->getRequest()->getParam('status');
            $statusField = $this->getStatusField();

            if (null === $status) {
                throw new LocalizedException(__('Parameter "Status" missing in request data.'));
            }
            if (null === $statusField) {
                throw new LocalizedException(__('Status Field Name is not specified.'));
            }

            $repository = $this->getRepository();
            foreach ($this->getMassActionModels() as $item) {
                $item->setData($statusField, $status);
                $repository->save($item);
                $count++;
            }
        } catch (LocalizedException $e) {
            $error = true;
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Exception $e) {
            $error = true;
            $this->messageManager->addExceptionMessage(
                $e,
                __(
                    "We can't change status of %1 right now. %2",
                    strtolower($this->getEntityTitle()),
                    $e->getMessage()
                )
            );
        }

        if (!$error) {
            $this->messageManager->addSuccessMessage(
                __('%1 status have been changed.', $this->getEntityTitle($count > 1))
            );
        }

        return $this->resultRedirectFactory->create()->setPath('*/*');
    }

    /**
     * Go to config section action
     *
     * @return ResultInterface
     */
    protected function configAction()
    {
        return $this->resultRedirectFactory->create()->setPath(
            'admin/system_config/edit',
            ['section' => static::CONFIG_SECTION]
        );
    }

    /**
     * Retrieve models selected for mass action, loaded with one repository getList() call.
     *
     * @return AbstractModel[]
     * @throws LocalizedException
     */
    protected function getMassActionModels()
    {
        $ids = $this->getMassActionIds();
        if (!$ids) {
            return [];
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter($this->createModel()->getIdFieldName(), $ids, 'in')
            ->create();

        return $this->getRepository()->getList($searchCriteria)->getItems();
    }

    /**
     * Retrieve entity ids from request param or from UI grid mass action selection.
     *
     * @return array
     * @throws LocalizedException
     */
    protected function getMassActionIds()
    {
        $ids = $this->getRequest()->getParam(static::ID_KEY);
        if ($ids) {
            return is_array($ids) ? $ids : [$ids];
        }

        $request = $this->getRequest();
        $selected = $request->getParam(Filter::SELECTED_PARAM);
        $excluded = $request->getParam(Filter::EXCLUDED_PARAM);
        if ('false' !== $excluded
            && !(is_array($excluded) && $excluded)
            && !(is_array($selected) && $selected)
        ) {
            throw new LocalizedException(__('An item needs to be selected. Select and try again.'));
        }

        // Applies grid filters and selection to the listing data provider
        $this->filter->applySelectionOnTargetProvider();
        $dataProvider = $this->filter->getComponent()->getContext()->getDataProvider();

        if ($dataProvider instanceof \Magento\Ui\DataProvider\AbstractDataProvider) {
            $ids = $dataProvider->getAllIds();
        } else {
            $ids = [];
            $dataProvider->setLimit(0, false);
            foreach ($dataProvider->getSearchResult()->getItems() as $item) {
                $ids[] = $item->getId();
            }
        }

        if (is_array($selected)) {
            $ids = array_unique(array_merge($ids, $selected));
        }

        return $ids;
    }

    /**
     * Retrieve current model: loaded by request id via repository or a new one.
     *
     * @param bool $load
     * @return AbstractModel
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function getModel($load = true)
    {
        if (null === $this->model) {
            $id = (int)$this->getRequest()->getParam(static::ID_KEY);
            if (!$id) {
                $idFieldName = $this->createModel()->getIdFieldName();
                if (static::ID_KEY !== $idFieldName) {
                    $id = (int)$this->getRequest()->getParam($idFieldName);
                }
            }

            if (!$id || !$load) {
                return $this->model = $this->createModel();
            }

            $this->model = $this->getRepository()->getById($id);
            $this->_eventManager->dispatch(
                $this->getEventPrefix() . '_form_load_model_after',
                ['model' => $this->model]
            );
        }
        return $this->model;
    }

    /**
     * Retrieve event name prefix, e.g. "magefan_blog_post".
     *
     * @return string
     */
    protected function getEventPrefix()
    {
        $request = $this->getRequest();
        return 'magefan_' . $request->getModuleName() . '_' . $request->getControllerName();
    }

    /**
     * Save or clear form data in data persistor.
     *
     * @param mixed $data
     * @return $this
     */
    protected function setFormData($data = null)
    {
        if (null === $data) {
            $data = $this->getRequest()->getParams();
        }

        if (false === $data) {
            $this->dataPersistor->clear($this->getFormSessionKey());
        } else {
            $this->dataPersistor->set($this->getFormSessionKey(), $data);
        }

        return $this;
    }

    /**
     * Set active menu item if menu block exists on the page.
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     * @return void
     */
    protected function setActiveMenuOnPage($resultPage)
    {
        if ($resultPage->getLayout()->getBlock('menu')) {
            $resultPage->setActiveMenu($this->getActiveMenu());
        }
    }

    /**
     * Filter request params before they are added to the model.
     *
     * @param array $data
     * @return array
     */
    protected function filterParams($data)
    {
        return $data;
    }

    // @codingStandardsIgnoreStart
    /**
     * Before model save
     *
     * @param AbstractModel $model
     * @param \Magento\Framework\App\RequestInterface $request
     * @return void
     */
    protected function beforeSave($model, $request)
    {
    }

    /**
     * After model save
     *
     * @param AbstractModel $model
     * @param \Magento\Framework\App\RequestInterface $request
     * @return void
     */
    protected function afterSave($model, $request)
    {
    }

    /**
     * Before action
     *
     * @return void
     */
    protected function beforeAction()
    {
    }

    /**
     * After action
     *
     * @return void
     */
    protected function afterAction()
    {
    }
    // @codingStandardsIgnoreEnd
}

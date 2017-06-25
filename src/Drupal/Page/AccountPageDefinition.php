<?php

namespace MakinaCorpus\Dashboard\Drupal\Page;

use MakinaCorpus\Dashboard\DependencyInjection\DynamicPageDefinition;
use MakinaCorpus\Dashboard\Drupal\Datasource\DefaultAccountDatasource;
use MakinaCorpus\Dashboard\View\Html\TwigView;

/**
 * Default node admin page implementation, suitable for most use cases
 *
 * @todo
 *   - handle virtual columns (non existing in property info)
 *      -> allow non existing columns in property info to be displayed in dynamic template
 *      -> even if value is null, execute callback on it in twig extension
 *   - check for method parameters names (if value => value, if item => item)
 *      -> do a proxy callback in the parent implementation that passes the right
 *         value that the user await here
 */
class AccountPageDefinition extends DynamicPageDefinition
{
    protected $datasourceId = DefaultAccountDatasource::class;
    protected $viewType = TwigView::class;

    public $uid = 0;
    public $name = '';
    public $mail = '';
    public $created = 0;
    public $changed = 0;
    public $lastAccess = 0;
    public $login = 0;
    public $timezone = '';
    public $language = '';

    /**
     * Renders name
     */
    public function renderName($value, array $options, $item)
    {
        return '<a href="' . url('user/' . $item->uid) . '" title="' . t("View user profile") . '">' . check_plain($value) . '</a>';
    }

    /**
     * Renders mail
     */
    public function renderMail($value, array $options, $item)
    {
        return '<a href="mailto:' . check_plain($value) . '" title="' . t("Send e-mail") . '">' . check_plain($value) . '</a>';
    }

    public function renderLanguage($value)
    {
        if ($value === LANGUAGE_NONE) {
            return t("None");
        }

        _locale_prepare_predefined_list();
        $list = _locale_get_predefined_list();

        if (isset($list[$value])) {
            return t($list[$value][0]);
        }

        return check_plain($value);
    }

    /**
     * Renders created
     */
    public function renderCreated($value)
    {
        return format_interval(time() - $value);
    }

    /**
     * Renders changed
     */
    public function renderChanged($value)
    {
        return $value ? format_interval(time() - $value) : t("Never");
    }

    /**
     * Renders login
     */
    public function renderLogin($value)
    {
        return $value ? format_interval(time() - $value) : t("Never");
    }

    /**
     * Renders access
     */
    public function renderLastAccess($value, array $options, $item)
    {
        return $item->access ? format_interval(time() - $item->access) : t("Never");
    }
}

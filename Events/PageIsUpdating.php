<?php

namespace Modules\Page\Events;

use Modules\Core\Contracts\EntityIsChanging;
use Modules\Core\Events\AbstractEntityHook;
use Modules\Page\Entities\Page;

class PageIsUpdating extends AbstractEntityHook implements EntityIsChanging
{
    /**
     * @var Page
     */
    private Page $page;

    public function __construct(Page $page, array $attributes)
    {
        $this->page = $page;
        parent::__construct($attributes);
    }

    /**
     * @return Page
     */
    public function getPage(): Page
    {
        return $this->page;
    }
}

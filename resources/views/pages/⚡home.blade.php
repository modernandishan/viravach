<?php

use App\Livewire\Concerns\RecordsPageView;
use App\Models\GeneralSetting;
use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component {

    use RecordsPageView;

    public Page $page;
    public GeneralSetting $gs;

    public function mount(): void
    {
        $this->page = Page::where('slug', '/')->firstOrFail();
        $this->gs = GeneralSetting::current();
        $this->recordPageView($this->page);
    }

    public function render()
    {
        $pageTitle = $this->page->getTranslation('title', app()->getLocale());
        $tagline = $this->gs->getTranslation('site_tagline', app()->getLocale());

        return $this->view()->title("{$pageTitle} | {$tagline}");
    }

};
?>

<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">

    <div class="content flex-row-fluid" id="kt_content">
        <livewire:home.advance-search/>
    </div>



</div>

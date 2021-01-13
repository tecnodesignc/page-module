<?php

namespace Modules\Page\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Modules\Core\Http\Controllers\BasePublicController;
use Modules\Menu\Repositories\MenuItemRepository;
use Modules\Page\Entities\Page;
use Modules\Page\Repositories\PageRepository;

class PublicController extends BasePublicController
{
    /**
     * @var PageRepository
     */
    private $page;
    /**
     * @var Application
     */
    private $app;

    private $disabledPage = false;

    public function __construct(PageRepository $page, Application $app)
    {
        parent::__construct();
        $this->page = $page;
        $this->app = $app;
    }

    /**
     * @param $slug
     * @return \Illuminate\View\View
     */
    public function uri($slug)
    {
        $page = $this->findPageForSlug($slug);

        $this->throw404IfNotFound($page);

        $currentTranslatedPage = $page->getTranslation(locale());
        if ($slug !== $currentTranslatedPage->slug) {

            return redirect()->to($currentTranslatedPage->locale . '/' . $currentTranslatedPage->slug, 301);
        }

        $template = $this->getTemplateForPage($page);

        $alternate = $this->getAlternateMetaData($page);

        return view($template, compact('page', 'alternate'));
    }

    /**
     * @return \Illuminate\View\View
     */
    public function homepage()
    {
        $page = $this->page->findHomepage();

        $this->throw404IfNotFound($page);

        $template = $this->getTemplateForPage($page);

        $alternate = $this->getAlternateMetaData($page);

        return view($template, compact('page', 'alternate'));
    }

    /**
     * Find a page for the given slug.
     * The slug can be a 'composed' slug via the Menu
     * @param string $slug
     * @return Page
     */
    private function findPageForSlug($slug)
    {
        $menuItem = app(MenuItemRepository::class)->findByUriInLanguage($slug, locale());

        if ($menuItem) {
            return $this->page->find($menuItem->page_id);
        }

        return $this->page->findBySlug($slug);
    }

    /**
     * Return the template for the given page
     * or the default template if none found
     * @param $page
     * @return string
     */
    private function getTemplateForPage($page)
    {
        return (view()->exists($page->template)) ? $page->template : 'default';
    }

    /**
     * Throw a 404 error page if the given page is not found or draft
     * @param $page
     */
    private function throw404IfNotFound($page)
    {
        if (null === $page || $page->status === $this->disabledPage) {
            $this->app->abort('404');
        }
    }

    /**
     * Create a key=>value array for alternate links
     *
     * @param $page
     *
     * @return array
     */
    private function getAlternateMetaData($page)
    {
        $translations = $page->getTranslationsArray();

        $alternate = [];
        foreach ($translations as $locale => $data) {
            $alternate[$locale] = $data['slug'];
        }

        return $alternate;
    }

    public function inlinesave() {


        if(!$this->auth->hasAccess('page.pages.edit')) return;

        try {

            $request = Request::all();
            $inlinedata = $request["inlinedata"];

            if($request["type"]=="page") {


                if(\LaravelLocalization::getDefaultLocale()==\LaravelLocalization::getCurrentLocale()) {
                    if (!empty($request["id"])) $tplpath = base_path('Themes/'.setting('core::template').'/views/pages/content/' . intval($request["id"]) . '.blade.php');
                } else {
                    if (!empty($request["id"])) $tplpath = base_path('Themes/'.setting('core::template').'/views/pages/content/'.\LaravelLocalization::getCurrentLocale() ."/". intval($request["id"]) . '.blade.php');
                }


                $html = file_get_contents($tplpath);

                $dom = new DomDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML($html, LIBXML_HTML_NODEFDTD);
                libxml_clear_errors();
                //$dom->loadHTMLFile($tplpath, LIBXML_HTML_NODEFDTD);

                $finder = new DomXPath($dom);
                $classname="icontenteditable";

                $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");


                foreach($inlinedata as $k=>$inlineitem) {
                    if(!empty($nodes) && is_object($nodes->item($k))) {
                        $nodes->item($k)->nodeValue = '';
                        $this->appendHTML($nodes->item($k), $inlineitem);
                    }
                }

                $dom->saveHTMLFile($tplpath);

                $html = file_get_contents($tplpath);
                $html = str_replace(['<html><body>','</body></html>'],'',$html);


                //Todo: Fix this replace.
                $html = str_replace('$page-&gt;','$page->',$html);
                $html = str_replace('view()-&gt;','view()->',$html);
                $html = str_replace('%7B%7B','{{',$html);
                $html = str_replace('%7D%7D','}}',$html);
                $html = str_replace("=&gt;","=>",$html);



                file_put_contents($tplpath,$html);


                return response()->json(['success'=>'true']);
            }


        } catch(\Throwable $t) {
            \Log::error($t);
        }

        return response()->json(['success'=>'false']);

    }

    function appendHTML($parent, $source) {
        $tmpDoc = new DOMDocument();
        $tmpDoc->loadHTML($source);
        foreach ($tmpDoc->getElementsByTagName('body')->item(0)->childNodes as $node) {
            $node = $parent->ownerDocument->importNode($node,true);
            $parent->appendChild($node);
        }
    }
}

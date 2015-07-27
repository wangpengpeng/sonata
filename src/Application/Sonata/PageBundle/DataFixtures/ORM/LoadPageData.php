<?php

namespace Application\Sonata\PageBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Sonata\PageBundle\Model\SiteInterface;
use Sonata\PageBundle\Model\PageInterface;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadPageData extends AbstractFixture implements ContainerAwareInterface, OrderedFixtureInterface
{
    private $container;

    public function getOrder()
    {
        return 4;
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $site = $this->createSite();
        $this->createGlobalPage($site);
        $this->createHomePage($site);
        $this->createPlainHtmlPage($site, 'about');

        $this->create404ErrorPage($site);
        $this->create500ErrorPage($site);

    }

    /**
     * @return SiteInterface $site
     */
    public function createSite()
    {
        $site = $this->getSiteManager()->create();

        $site->setHost('localhost');
        $site->setEnabled(true);
        $site->setName('localhost');
        $site->setEnabledFrom(new \DateTime('now'));
        $site->setEnabledTo(new \DateTime('+10 years'));
        $site->setRelativePath("");
        $site->setIsDefault(true);

        $this->getSiteManager()->save($site);

        return $site;
    }



    /**
     * @param SiteInterface $site
     */
    public function createHomePage(SiteInterface $site)
    {
        $pageManager = $this->getPageManager();
        $blockManager = $this->getBlockManager();
        $blockInteractor = $this->getBlockInteractor();

        $this->addReference('page-homepage', $homepage = $pageManager->create());
        $homepage->setSlug('/');
        $homepage->setUrl('/');
        $homepage->setName('Home');
        $homepage->setTitle('Homepage');
        $homepage->setEnabled(true);
        $homepage->setDecorate(0);
        $homepage->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
        $homepage->setTemplateCode('default');
        $homepage->setRouteName(PageInterface::PAGE_ROUTE_CMS_NAME);
        $homepage->setSite($site);

        $pageManager->save($homepage);

        // CREATE A HEADER BLOCK
        $homepage->addBlocks($contentTop = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page' => $homepage,
            'code' => 'content_top',
        )));

        $contentTop->setName('The container top container');

        $blockManager->save($contentTop);

        // add a block text
        $contentTop->addChildren($text = $blockManager->create());
        $text->setType('sonata.block.service.text');
        $text->setSetting(
            'content',
            \file_get_contents(__DIR__ . '/../data/html/home.html')
        );
        $text->setPosition(1);
        $text->setEnabled(true);
        $text->setPage($homepage);


        $homepage->addBlocks($content = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page' => $homepage,
            'code' => 'content',
        )));
        $content->setName('The content container');
        $blockManager->save($content);

        $content->addChildren($moreText = $blockManager->create());
        $moreText->setType('sonata.block.service.text');
        $moreText->setSetting('content', '');
        $moreText->setPosition(1);
        $moreText->setEnabled(true);
        $moreText->setPage($homepage);


        // Add homepage bottom container
        $homepage->addBlocks($bottom = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page'    => $homepage,
            'code'    => 'content_bottom',
        ), function ($container) {
            $container->setSetting('layout', '{{ CONTENT }}');
        }));
        $bottom->setName('The bottom content container');

        $pageManager->save($homepage);
    }

    public function createPlainHtmlPage(SiteInterface $site, $name)
    {

        $pageManager = $this->getPageManager();
        $blockManager = $this->getBlockManager();
        $blockInteractor = $this->getBlockInteractor();

        $nameLowerCasedAndUnderscored = str_replace(' ', '-', strtolower($name));

        $this->addReference('page' . $nameLowerCasedAndUnderscored, $page = $pageManager->create());

        $page->setSlug('/' . $nameLowerCasedAndUnderscored);
        $page->setUrl('/' . $nameLowerCasedAndUnderscored);
        $page->setName($name);
        $page->setTitle($name);
        $page->setEnabled(true);
        $page->setDecorate(0);
        $page->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
        $page->setTemplateCode('default');
        $page->setRouteName(PageInterface::PAGE_ROUTE_CMS_NAME);
        $page->setParent($this->getReference('page-homepage'));
        $page->setSite($site);

        $pageManager->save($page);

        // CREATE A HEADER BLOCK
        $page->addBlocks($contentTop = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page' => $page,
            'code' => 'content_top',
        )));

        $contentTop->setName('The container top container');

        $blockManager->save($contentTop);

        // add a block text
        $contentTop->addChildren($text = $blockManager->create());
        $text->setType('sonata.block.service.text');
        $text->setSetting(
            'content',
            \file_get_contents(sprintf(
                '%s/../data/html/%s.html',
                __DIR__,
                $nameLowerCasedAndUnderscored
            ))
        );
        $text->setPosition(1);
        $text->setEnabled(true);
        $text->setPage($page);
    }

    public function create404ErrorPage(SiteInterface $site)
    {
        $pageManager = $this->getPageManager();
        $blockManager = $this->getBlockManager();
        $blockInteractor = $this->getBlockInteractor();

        $page = $pageManager->create();
        $page->setName('_page_internal_error_not_found');
        $page->setTitle('Error 404');
        $page->setEnabled(true);
        $page->setDecorate(1);
        $page->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
        $page->setTemplateCode('default');
        $page->setRouteName('_page_internal_error_not_found');
        $page->setSite($site);

        $page->addBlocks($block = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page'    => $page,
            'code'    => 'content_top',
        )));

        // add the breadcrumb
        $block->addChildren($breadcrumb = $blockManager->create());
        $breadcrumb->setType('sonata.page.block.breadcrumb');
        $breadcrumb->setPosition(0);
        $breadcrumb->setEnabled(true);
        $breadcrumb->setPage($page);

        // Add text content block
        $block->addChildren($text = $blockManager->create());
        $text->setType('sonata.block.service.text');
        $text->setSetting('content', '<h2>Error 404</h2><div>Page not found.</div>');
        $text->setPosition(1);
        $text->setEnabled(true);
        $text->setPage($page);

        $pageManager->save($page);
    }


    public function create500ErrorPage(SiteInterface $site)
    {
        $pageManager = $this->getPageManager();
        $blockManager = $this->getBlockManager();
        $blockInteractor = $this->getBlockInteractor();

        $page = $pageManager->create();
        $page->setName('_page_internal_error_fatal');
        $page->setTitle('Error 500');
        $page->setEnabled(true);
        $page->setDecorate(1);
        $page->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
        $page->setTemplateCode('default');
        $page->setRouteName('_page_internal_error_fatal');
        $page->setSite($site);

        $page->addBlocks($block = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page'    => $page,
            'code'    => 'content_top',
        )));

        // add the breadcrumb
        $block->addChildren($breadcrumb = $blockManager->create());
        $breadcrumb->setType('sonata.page.block.breadcrumb');
        $breadcrumb->setPosition(0);
        $breadcrumb->setEnabled(true);
        $breadcrumb->setPage($page);

        // Add text content block
        $block->addChildren($text = $blockManager->create());
        $text->setType('sonata.block.service.text');
        $text->setSetting('content', '<h2>Error 500</h2><div>Internal error.</div>');
        $text->setPosition(1);
        $text->setEnabled(true);
        $text->setPage($page);

        $pageManager->save($page);
    }

    /**
     * @param SiteInterface $site
     */
    public function createGlobalPage(SiteInterface $site)
    {
        $pageManager = $this->getPageManager();
        $blockManager = $this->getBlockManager();
        $blockInteractor = $this->getBlockInteractor();

        $global = $pageManager->create();
        $global->setName('global');
        $global->setRouteName('_page_internal_global');
        $global->setSite($site);

        $pageManager->save($global);

        // CREATE A HEADER BLOCK
        $global->addBlocks($header = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page' => $global,
            'code' => 'header',
        )));

        $header->setName('The header container');


        $global->addBlocks($headerMenu = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page' => $global,
            'code' => 'header-menu',
        )));

        $headerMenu->setPosition(2);

        $header->addChildren($headerMenu);

        $headerMenu->setName('The header menu container');
        $headerMenu->setPosition(1);
        $headerMenu->addChildren($menu = $blockManager->create());

        $menu->setType('sonata.block.service.menu');
        $menu->setSetting('menu_name', "ApplicationSonataPageBundle:Builder:mainMenu");
        $menu->setSetting('safe_labels', true);
        $menu->setPosition(1);
        $menu->setEnabled(true);
        $menu->setPage($global);



        $global->addBlocks($footerMenuContainer = $blockInteractor->createNewContainer(array(
            'enabled' => true,
            'page' => $global,
            'code' => 'footer',
        )));

        $footerMenuContainer->setName('The footer menu container');
        $footerMenuContainer->setPosition(1);
        $footerMenuContainer->addChildren($footerMenu = $blockManager->create());

        $footerMenu->setType('sonata.block.service.menu');
        $footerMenu->setSetting('menu_name', "ApplicationSonataPageBundle:Builder:footerMenu");
        $footerMenu->setSetting('safe_labels', true);
        $footerMenu->setPosition(1);
        $footerMenu->setEnabled(true);
        $footerMenu->setPage($global);


        $pageManager->save($global);
    }

    /**
     * @return \Sonata\PageBundle\Model\SiteManagerInterface
     */
    public function getSiteManager()
    {
        return $this->container->get('sonata.page.manager.site');
    }

    /**
     * @return \Sonata\PageBundle\Model\PageManagerInterface
     */
    public function getPageManager()
    {
        return $this->container->get('sonata.page.manager.page');
    }

    /**
     * @return \Sonata\BlockBundle\Model\BlockManagerInterface
     */
    public function getBlockManager()
    {
        return $this->container->get('sonata.page.manager.block');
    }

    /**
     * @return \Sonata\PageBundle\Entity\BlockInteractor
     */
    public function getBlockInteractor()
    {
        return $this->container->get('sonata.page.block_interactor');
    }
} 
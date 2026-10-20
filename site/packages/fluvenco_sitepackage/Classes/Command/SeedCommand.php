<?php

declare(strict_types=1);

namespace Amrina\FluvencoSitepackage\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Builds the demo site through the DataHandler, the same API the backend uses:
 * page tree, content, image references, translations (EN, FR) and editor groups.
 */
final class SeedCommand extends Command
{
    private const LANGUAGES = ['en' => 1, 'fr' => 2];

    private array $pageUids = [];
    private array $fileUids = [];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly StorageRepository $storageRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        Bootstrap::initializeBackendAuthentication();

        $pageCount = $this->connectionPool->getConnectionForTable('pages')->count('uid', 'pages', ['deleted' => 0]);
        if ($pageCount > 1) {
            $io->error('The page tree already has content. Run this command on a fresh installation only.');
            return Command::FAILURE;
        }

        $site = json_decode(
            (string)file_get_contents(__DIR__ . '/../../Resources/Private/Seed/content.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->removeSetupTemplate();
        $this->importImages();

        // 1. Pages in the default language (German)
        $this->pageUids['root'] = 1;
        $this->updateRecord('pages', 1, $this->pageFields($site['root'], 'de') + ['is_siteroot' => 1]);
        $this->createPages($site['pages'], 1);
        $io->writeln(sprintf('Pages created: %d', count($this->pageUids)));

        // 2. Content per page, now that every page uid is known for links
        $contentCount = 0;
        $tree = array_merge([$site['root'] + ['key' => 'root']], $this->flatten($site['pages']));
        foreach ($tree as $page) {
            $previous = null;
            foreach ($page['content'] ?? [] as $element) {
                $previous = $this->createContent($element, $this->pageUids[$page['key']], $previous);
                $contentCount++;
            }
        }
        $io->writeln(sprintf('Content elements created: %d', $contentCount));

        // 3. Translations (connected mode: every record points to its German original)
        foreach (self::LANGUAGES as $lang => $languageId) {
            foreach ($tree as $page) {
                $this->translatePage($page, $lang, $languageId);
            }
        }
        $io->writeln('Translations created: English, French');

        $this->createEditorGroups();
        $this->writeSiteSettings();

        $io->success('Fluvenco demo site is ready.');
        return Command::SUCCESS;
    }

    private function createPages(array $pages, int $parentUid): void
    {
        $previousUid = null;
        foreach ($pages as $page) {
            $fields = $this->pageFields($page, 'de');
            $fields['doktype'] = $page['doktype'] ?? 1;
            $fields['hidden'] = 0;
            // A negative pid places the page after its previous sibling, so the menu keeps the JSON order
            $fields['pid'] = $previousUid ? -$previousUid : $parentUid;
            $uid = $this->insertRecord('pages', $fields, $page['media'] ?? null, 'media', $page['title']['de'], null);
            $this->pageUids[$page['key']] = $uid;
            $previousUid = $uid;
            if (!empty($page['children'])) {
                $this->createPages($page['children'], $uid);
            }
        }
    }

    private function createContent(array $element, int $pageUid, ?int $previousUid): int
    {
        $fields = $this->contentFields($element, 'de');
        $fields['pid'] = $previousUid ? -$previousUid : $pageUid;
        $fields['CType'] = $element['CType'];
        $fields['colPos'] = 0;
        if (isset($element['imageorient'])) {
            $fields['imageorient'] = $element['imageorient'];
        }
        if (isset($element['table_header_position'])) {
            $fields['table_header_position'] = $element['table_header_position'];
            $fields['table_delimiter'] = 124;
        }
        if (isset($element['pages'])) {
            $fields['pages'] = (string)$this->pageUids[ltrim($element['pages'], '@')];
        }
        if (isset($element['header_link'])) {
            $fields['header_link'] = 't3://page?uid=' . $this->pageUids[ltrim($element['header_link'], '@')];
        }
        $alt = isset($element['alt']) ? $element['alt']['de'] : '';
        // textmedia keeps its media in "assets", the hero element uses "image"
        $imageField = $element['CType'] === 'textmedia' ? 'assets' : 'image';
        return $this->insertRecord('tt_content', $fields, $element['image'] ?? null, $imageField, $alt, $pageUid);
    }

    private function translatePage(array $page, string $lang, int $languageId): void
    {
        $pageUid = $this->pageUids[$page['key']];
        $mapping = $this->localize('pages', $pageUid, $languageId);
        $this->updateRecord('pages', (int)$mapping['pages'][$pageUid], $this->pageFields($page, $lang) + ['hidden' => 0]);
        $this->translateReferences($mapping, $page['title'][$lang]);

        $contentUids = $this->connectionPool->getConnectionForTable('tt_content')->select(
            ['uid'],
            'tt_content',
            ['pid' => $pageUid, 'sys_language_uid' => 0, 'deleted' => 0],
            [],
            ['sorting' => 'ASC']
        )->fetchFirstColumn();

        foreach (array_values($page['content'] ?? []) as $index => $element) {
            $originalUid = (int)$contentUids[$index];
            $mapping = $this->localize('tt_content', $originalUid, $languageId);
            $this->updateRecord('tt_content', (int)$mapping['tt_content'][$originalUid], $this->contentFields($element, $lang) + ['hidden' => 0]);
            if (isset($element['alt'])) {
                $this->translateReferences($mapping, $element['alt'][$lang]);
            }
        }
    }

    private function pageFields(array $page, string $lang): array
    {
        $fields = ['title' => $page['title'][$lang]];
        $slug = $page['slug'] ?? '/';
        $fields['slug'] = is_array($slug) ? $slug[$lang] : $slug;
        foreach (['abstract', 'description', 'seo_title'] as $field) {
            if (isset($page[$field])) {
                $fields[$field] = $page[$field][$lang];
            }
        }
        return $fields;
    }

    private function contentFields(array $element, string $lang): array
    {
        $fields = [];
        foreach (['header', 'subheader', 'bodytext'] as $field) {
            if (isset($element[$field])) {
                $fields[$field] = $this->resolveLinks($element[$field][$lang]);
            }
        }
        if (isset($element['header_layout'])) {
            $fields['header_layout'] = $element['header_layout'];
        }
        return $fields;
    }

    private function resolveLinks(string $text): string
    {
        return (string)preg_replace_callback(
            '/t3:\/\/page\?uid=@([a-z0-9]+)/',
            fn(array $match): string => 't3://page?uid=' . $this->pageUids[$match[1]],
            $text
        );
    }

    private function insertRecord(string $table, array $fields, ?string $image, string $imageField, string $alt, ?int $pageUid): int
    {
        $data = [$table => ['NEW1' => $fields]];
        $uid = (int)$this->process($data)->substNEWwithIDs['NEW1'];
        if ($image !== null) {
            // Page images live on the page itself, content images on the page that holds the element
            $this->process(['sys_file_reference' => ['NEW2' => [
                'uid_local' => $this->fileUids[$image],
                'tablenames' => $table,
                'fieldname' => $imageField,
                'uid_foreign' => $uid,
                'pid' => $pageUid ?? $uid,
                'alternative' => $alt,
            ]], $table => [$uid => [$imageField => 'NEW2']]]);
        }
        return $uid;
    }

    private function updateRecord(string $table, int $uid, array $fields): void
    {
        $this->process([$table => [$uid => $fields]]);
    }

    private function localize(string $table, int $uid, int $languageId): array
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [$table => [$uid => ['localize' => $languageId]]]);
        $dataHandler->process_cmdmap();
        $this->assertNoErrors($dataHandler);
        return $dataHandler->copyMappingArray_merged;
    }

    private function process(array $data): DataHandler
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
        $this->assertNoErrors($dataHandler);
        return $dataHandler;
    }

    private function assertNoErrors(DataHandler $dataHandler): void
    {
        if ($dataHandler->errorLog !== []) {
            throw new \RuntimeException(implode(PHP_EOL, $dataHandler->errorLog), 1729420000);
        }
    }

    private function translateReferences(array $mapping, string $alt): void
    {
        foreach ($mapping['sys_file_reference'] ?? [] as $translatedReference) {
            $this->updateRecord('sys_file_reference', (int)$translatedReference, ['alternative' => $alt]);
        }
    }

    private function flatten(array $pages): array
    {
        $flat = [];
        foreach ($pages as $page) {
            $flat[] = $page;
            $flat = array_merge($flat, $this->flatten($page['children'] ?? []));
        }
        return $flat;
    }

    private function removeSetupTemplate(): void
    {
        // The install wizard creates a sys_template record; the site set provides TypoScript instead
        $this->connectionPool->getConnectionForTable('sys_template')->update('sys_template', ['deleted' => 1], ['pid' => 1]);
    }

    private function importImages(): void
    {
        $storage = $this->storageRepository->getDefaultStorage();
        $folder = $storage->hasFolder('fluvenco') ? $storage->getFolder('fluvenco') : $storage->createFolder('fluvenco');
        foreach (glob(__DIR__ . '/../../Resources/Private/Seed/images/*.svg') as $path) {
            $temporary = Environment::getVarPath() . '/transient/' . basename($path);
            copy($path, $temporary);
            $file = $storage->addFile($temporary, $folder, basename($path), DuplicationBehavior::REPLACE);
            $this->fileUids[basename($path)] = $file->getUid();
        }
    }

    private function createEditorGroups(): void
    {
        $common = [
            'pid' => 0,
            'groupMods' => 'web_layout,web_list,web_ViewpageView,media_management',
            'tables_select' => 'pages,tt_content,sys_file,sys_file_reference,sys_file_metadata',
            'tables_modify' => 'pages,tt_content,sys_file_reference,sys_file_metadata',
            'pagetypes_select' => '1,4,254',
            'db_mountpoints' => '1',
            'explicit_allowdeny' => 'tt_content:CType:header,tt_content:CType:text,tt_content:CType:textmedia,'
                . 'tt_content:CType:bullets,tt_content:CType:table,tt_content:CType:fluvenco_hero,tt_content:CType:fluvenco_cards,'
                . 'tt_content:CType:menu_subpages',
            'non_exclude_fields' => 'pages:title,pages:slug,pages:nav_title,pages:abstract,pages:description,pages:seo_title,'
                . 'pages:hidden,pages:media,tt_content:header,tt_content:subheader,tt_content:bodytext,tt_content:image,'
                . 'tt_content:assets,tt_content:header_link,tt_content:pages,tt_content:imageorient,tt_content:hidden,sys_file_reference:alternative',
        ];
        $this->process(['be_groups' => [
            'NEW1' => $common + [
                'title' => 'Redaktion (alle Sprachen)',
                'description' => 'Zentrale Redaktion: pflegt Inhalte in allen Sprachen und gibt Seiten frei.',
                'allowed_languages' => '0,1,2',
            ],
            'NEW2' => $common + [
                'title' => 'Übersetzung Französisch',
                'description' => 'Länderredaktion Frankreich: übersetzt und pflegt nur französische Inhalte.',
                'allowed_languages' => '2',
            ],
            'NEW3' => $common + [
                'title' => 'Übersetzung Englisch',
                'description' => 'Länderredaktion international: übersetzt und pflegt nur englische Inhalte.',
                'allowed_languages' => '1',
            ],
        ]]);
    }

    private function writeSiteSettings(): void
    {
        $settings = sprintf("fluvenco:\n  footerFolder: %d\n", $this->pageUids['footer']);
        file_put_contents(Environment::getConfigPath() . '/sites/fluvenco/settings.yaml', $settings);
    }
}

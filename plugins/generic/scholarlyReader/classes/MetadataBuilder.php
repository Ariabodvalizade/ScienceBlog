<?php

/**
 * @file plugins/generic/scholarlyReader/classes/MetadataBuilder.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class MetadataBuilder
 *
 * @brief schema.org JSON-LD (ScholarlyArticle) and OpenGraph/Twitter meta
 *  tags for an article landing page. Complements the Google Scholar
 *  ("citation_*") and Dublin Core tags produced by the bundled OJS plugins.
 */

namespace APP\plugins\generic\scholarlyReader\classes;

use APP\core\Request;
use APP\issue\Issue;
use APP\journal\Journal;
use APP\publication\Publication;
use APP\submission\Submission;

class MetadataBuilder
{
    public function __construct(
        protected Request $request,
        protected Journal $journal,
        protected Submission $article,
        protected Publication $publication,
        protected ?Issue $issue,
        protected ?string $sectionTitle = null,
    ) {
    }

    public function articleUrl(): string
    {
        return $this->request->url(null, 'article', 'view', [$this->article->getBestId()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonLd(): array
    {
        $locale = $this->publication->getData('locale') ?: $this->journal->getPrimaryLocale();
        $title = strip_tags((string) $this->publication->getLocalizedFullTitle(null, 'html'));

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'ScholarlyArticle',
            '@id' => $this->articleUrl(),
            'url' => $this->articleUrl(),
            'headline' => mb_substr($title, 0, 110),
            'name' => $title,
            'inLanguage' => str_replace('_', '-', $locale),
            'isAccessibleForFree' => true,
            'author' => $this->authors(),
        ];

        if ($abstract = $this->plainAbstract()) {
            $data['abstract'] = $abstract;
        }
        if ($date = $this->publication->getData('datePublished')) {
            $data['datePublished'] = substr($date, 0, 10);
        }
        if ($modified = $this->publication->getData('lastModified')) {
            $data['dateModified'] = substr($modified, 0, 10);
        }
        if ($keywords = $this->keywords()) {
            $data['keywords'] = implode(', ', $keywords);
        }
        if ($this->sectionTitle) {
            $data['articleSection'] = $this->sectionTitle;
        }
        if ($license = $this->publication->getData('licenseUrl')) {
            $data['license'] = $license;
        }
        if ($pages = $this->publication->getData('pages')) {
            $data['pagination'] = $pages;
            if (preg_match('/^\s*(\w+)\s*[-–]\s*(\w+)\s*$/u', $pages, $m)) {
                $data['pageStart'] = $m[1];
                $data['pageEnd'] = $m[2];
            }
        }
        if ($doi = $this->doi()) {
            $data['identifier'] = [
                '@type' => 'PropertyValue',
                'propertyID' => 'DOI',
                'value' => $doi,
            ];
            $data['sameAs'] = 'https://doi.org/' . $doi;
        }

        $publisher = $this->journal->getData('publisherInstitution') ?: $this->journal->getLocalizedName();
        $data['publisher'] = ['@type' => 'Organization', 'name' => $publisher];

        $periodical = array_filter([
            '@type' => 'Periodical',
            'name' => $this->journal->getLocalizedName(),
            'issn' => array_values(array_filter([$this->journal->getData('onlineIssn'), $this->journal->getData('printIssn')])) ?: null,
            'url' => $this->request->url(null, 'index'),
            'publisher' => $data['publisher'],
        ]);

        if ($this->issue) {
            $issueNode = array_filter([
                '@type' => 'PublicationIssue',
                'issueNumber' => $this->issue->getNumber() ?: null,
                'datePublished' => $this->issue->getDatePublished() ? substr($this->issue->getDatePublished(), 0, 10) : null,
                'url' => $this->request->url(null, 'issue', 'view', [$this->issue->getBestIssueId()]),
            ]);
            if ($this->issue->getVolume()) {
                $issueNode['isPartOf'] = [
                    '@type' => 'PublicationVolume',
                    'volumeNumber' => (string) $this->issue->getVolume(),
                    'isPartOf' => $periodical,
                ];
            } else {
                $issueNode['isPartOf'] = $periodical;
            }
            $data['isPartOf'] = $issueNode;
            if ($cover = $this->issue->getLocalizedCoverImageUrl()) {
                $data['image'] = $cover;
            }
        } else {
            $data['isPartOf'] = $periodical;
        }

        $encodings = [];
        foreach ($this->publication->getData('galleys') ?? [] as $galley) {
            if ($galley->getData('urlRemote') || !$galley->getFileType()) {
                continue;
            }
            $encodings[] = [
                '@type' => 'MediaObject',
                'encodingFormat' => $galley->getFileType(),
                'contentUrl' => $this->request->url(null, 'article', 'download', [$this->article->getBestId(), $galley->getBestGalleyId()]),
                'name' => $galley->getGalleyLabel(),
            ];
        }
        if ($encodings) {
            $data['encoding'] = $encodings;
        }

        $citations = $this->publication->getData('citations') ?? [];
        if (count($citations)) {
            $data['citation'] = array_map(fn ($c) => [
                '@type' => 'CreativeWork',
                'name' => trim(strip_tags((string) $c->getRawCitation())),
            ], is_array($citations) ? array_values($citations) : $citations->values()->all());
        }

        return $data;
    }

    /**
     * OpenGraph + Twitter card tags.
     */
    public function metaTags(): string
    {
        $tags = [
            'og:type' => 'article',
            'og:site_name' => $this->journal->getLocalizedName(),
            'og:title' => strip_tags((string) $this->publication->getLocalizedFullTitle(null, 'html')),
            'og:url' => $this->articleUrl(),
            'og:description' => mb_substr($this->plainAbstract(), 0, 300),
            'article:published_time' => $this->publication->getData('datePublished') ? substr($this->publication->getData('datePublished'), 0, 10) : '',
            'article:section' => (string) $this->sectionTitle,
            'twitter:card' => 'summary',
        ];
        if ($this->issue && ($cover = $this->issue->getLocalizedCoverImageUrl())) {
            $tags['og:image'] = $cover;
        }
        $description = $this->plainAbstract();
        if (mb_strlen($description) > 160) {
            $description = rtrim(mb_substr($description, 0, 157), ' ,.;:') . '…';
        }
        $html = $description !== '' ? '<meta name="description" content="' . htmlspecialchars($description, ENT_QUOTES) . '">' . "\n" : '';
        foreach ($tags as $property => $content) {
            if ($content === '' || $content === null) {
                continue;
            }
            $attr = str_starts_with($property, 'twitter:') ? 'name' : 'property';
            $html .= '<meta ' . $attr . '="' . htmlspecialchars($property, ENT_QUOTES) . '" content="' . htmlspecialchars($content, ENT_QUOTES) . '">' . "\n";
        }
        foreach ($this->keywords() as $keyword) {
            $html .= '<meta property="article:tag" content="' . htmlspecialchars($keyword, ENT_QUOTES) . '">' . "\n";
        }
        return $html;
    }

    public function jsonLdScript(): string
    {
        $json = json_encode($this->jsonLd(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT);
        return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>';
    }

    /**
     * @return array<array<string, mixed>>
     */
    protected function authors(): array
    {
        $authors = [];
        foreach ($this->publication->getData('authors') ?? [] as $author) {
            $person = [
                '@type' => 'Person',
                'name' => $author->getFullName(false),
                'givenName' => $author->getLocalizedGivenName(),
            ];
            if ($family = $author->getLocalizedFamilyName()) {
                $person['familyName'] = $family;
            }
            $affiliations = [];
            foreach ($author->getAffiliations() as $affiliation) {
                if ($name = $affiliation->getLocalizedName()) {
                    $org = ['@type' => 'Organization', 'name' => $name];
                    if ($ror = $affiliation->getRor()) {
                        $org['sameAs'] = $ror;
                    }
                    $affiliations[] = $org;
                }
            }
            if ($affiliations) {
                $person['affiliation'] = count($affiliations) === 1 ? $affiliations[0] : $affiliations;
            }
            if ($orcid = $author->getData('orcid')) {
                $person['sameAs'] = $orcid;
                $person['identifier'] = $orcid;
            }
            $authors[] = $person;
        }
        return $authors;
    }

    /**
     * @return string[]
     */
    protected function keywords(): array
    {
        $keywords = $this->publication->getLocalizedData('keywords') ?? [];
        return array_values(array_filter(array_map(fn ($k) => is_array($k) ? ($k['name'] ?? '') : (string) $k, $keywords)));
    }

    protected function plainAbstract(): string
    {
        $abstract = (string) $this->publication->getLocalizedData('abstract');
        $abstract = preg_replace('#</p>\s*<p[^>]*>#i', ' ', $abstract);
        return trim(html_entity_decode(strip_tags($abstract), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    protected function doi(): ?string
    {
        $doiObject = $this->publication->getData('doiObject');
        return $doiObject ? $doiObject->getData('doi') : null;
    }
}

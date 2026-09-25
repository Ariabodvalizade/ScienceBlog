<?php

/**
 * @file plugins/generic/authorPages/classes/AuthorDirectory.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class AuthorDirectory
 *
 * @brief Index of everyone who has authored a published article in a journal.
 *
 * OJS stores authors per publication (contributors), separately from user
 * accounts. Contributors are grouped into one person by ORCID iD, otherwise by
 * e-mail address, otherwise by name. The profile photo, public bio and website
 * come from the matching user account (same e-mail), which authors manage
 * themselves under Profile › Public.
 *
 * The index is cached per journal and rebuilt when articles are (un)published.
 */

namespace APP\plugins\generic\authorPages\classes;

use APP\facades\Repo;
use APP\file\PublicFileManager;
use APP\submission\Submission;
use PKP\core\Core;
use PKP\user\User;

class AuthorDirectory
{
    public const CACHE_TTL = 3600;

    /** @var array<string, ?User> */
    protected array $userCache = [];

    public function __construct(protected int $contextId)
    {
    }

    /**
     * People keyed by their public key, sorted by family name.
     *
     * @return array<string, array{key: string, slug: string, name: string, givenName: string, familyName: string, orcid: ?string, affiliation: string, country: ?string, biography: string, email: ?string, submissionIds: int[], latest: string}>
     */
    public function all(): array
    {
        $cacheFile = self::cacheFile($this->contextId);
        if (is_readable($cacheFile) && filemtime($cacheFile) > time() - self::CACHE_TTL) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $people = $this->build();
        $dir = dirname($cacheFile);
        if (is_dir($dir) || @mkdir($dir, 0775, true)) {
            @file_put_contents($cacheFile, json_encode($people));
        }
        return $people;
    }

    public function get(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * The public key for a contributor (used in URLs).
     */
    public static function keyFor(?string $orcid, ?string $email, string $name): string
    {
        if ($orcid && preg_match('/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i', $orcid, $m)) {
            return strtoupper($m[1]);
        }
        if ($email) {
            return 'a' . substr(sha1(strtolower(trim($email))), 0, 12);
        }
        return 'n' . substr(sha1(mb_strtolower(trim($name))), 0, 12);
    }

    public static function slug(string $name): string
    {
        $ascii = function_exists('iconv') ? (@iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name) : $name;
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii)), '-') ?: 'author';
    }

    public static function cacheFile(int $contextId): string
    {
        return Core::getBaseDir() . "/cache/authorPages/directory-{$contextId}.json";
    }

    public static function invalidate(int $contextId): void
    {
        @unlink(self::cacheFile($contextId));
    }

    /**
     * Public URL of the photo uploaded by the matching user account, if any.
     */
    public function photoUrl(?string $email, string $baseUrl): ?string
    {
        $user = $this->userFor($email);
        $image = $user?->getData('profileImage');
        if (!$image || empty($image['uploadName'])) {
            return null;
        }
        $publicFileManager = new PublicFileManager();
        return $baseUrl . '/' . $publicFileManager->getSiteFilesPath() . '/' . rawurlencode($image['uploadName']);
    }

    public function userFor(?string $email): ?User
    {
        if (!$email) {
            return null;
        }
        $email = strtolower($email);
        if (!array_key_exists($email, $this->userCache)) {
            $this->userCache[$email] = Repo::user()->getByEmail($email);
        }
        return $this->userCache[$email];
    }

    /**
     * @return array<string, array>
     */
    protected function build(): array
    {
        $submissions = Repo::submission()->getCollector()
            ->filterByContextIds([$this->contextId])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->getMany();

        $people = [];
        foreach ($submissions as $submission) {
            $publication = $submission->getCurrentPublication();
            if (!$publication) {
                continue;
            }
            $date = (string) $publication->getData('datePublished');
            foreach ($publication->getData('authors') ?? [] as $author) {
                $name = trim($author->getFullName(false));
                if ($name === '') {
                    continue;
                }
                $key = self::keyFor($author->getData('orcid'), $author->getData('email'), $name);
                $isNewer = !isset($people[$key]) || strcmp($date, $people[$key]['latest']) >= 0;
                $entry = $people[$key] ?? ['submissionIds' => [], 'latest' => ''];
                $entry['submissionIds'][] = (int) $submission->getId();
                if ($isNewer) {
                    // Profile details come from the most recent article
                    $entry = array_merge($entry, [
                        'key' => $key,
                        'slug' => self::slug($name),
                        'name' => $name,
                        'givenName' => (string) $author->getLocalizedGivenName(),
                        'familyName' => (string) $author->getLocalizedFamilyName(),
                        'orcid' => $author->getData('orcid') ?: ($entry['orcid'] ?? null),
                        'affiliation' => (string) $author->getLocalizedAffiliationNamesAsString(null, ', '),
                        'country' => $author->getData('country') ?: null,
                        'biography' => (string) $author->getLocalizedData('biography') ?: ($entry['biography'] ?? ''),
                        'email' => $author->getData('email') ?: null,
                        'latest' => $date,
                    ]);
                }
                $people[$key] = $entry;
            }
        }

        foreach ($people as &$person) {
            $person['submissionIds'] = array_values(array_unique($person['submissionIds']));
        }
        unset($person);

        uasort($people, fn ($a, $b) => strcasecmp(
            ($a['familyName'] ?: $a['name']) . ' ' . $a['givenName'],
            ($b['familyName'] ?: $b['name']) . ' ' . $b['givenName']
        ));
        return $people;
    }
}

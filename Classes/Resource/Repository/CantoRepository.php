<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Repository;

use Ecentral\CantoSaasApiClient\Client;
use Ecentral\CantoSaasApiClient\Endpoint\Authorization\AuthorizationFailedException;
use Ecentral\CantoSaasApiClient\Endpoint\Authorization\NotAuthorizedException;
use Ecentral\CantoSaasApiClient\Http\Asset\GetContentDetailsRequest;
use Ecentral\CantoSaasApiClient\Http\Authorization\OAuth2Request;
use Ecentral\CantoSaasApiClient\Http\InvalidResponseException;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\GetDetailsRequest;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\GetTreeRequest;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\ListAlbumContentRequest;
use Ecentral\CantoSaasFal\Resource\CantoClientFactory;
use Ecentral\CantoSaasFal\Utility\CantoUtility;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class CantoRepository
{
    const REGISTRY_NAMESPACE = 'cantoSaasFal';
    const CANTO_CACHE_TAG_BLUEPRINT = 'canto_storage_%s';

    /**
     * The session token is valid for 30 days.
     * This property contains the time in seconds, until the token should be renewed.
     * Default: 29 days
     */
    protected int $sessionTokenValid = 2505600;

    protected Client $client;

    protected Registry $registry;

    protected FrontendInterface $cantoFolderCache;

    protected FrontendInterface $cantoFileCache;

    protected array $driverConfiguration;

    protected int $storageUid;

    protected string $cantoCacheTag;

    public function __construct(
        Registry $registry,
        FrontendInterface $cantoFolderCache,
        FrontendInterface $cantoFileCache
    ) {
        $this->registry = $registry;
        $this->cantoFolderCache = $cantoFolderCache;
        $this->cantoFileCache = $cantoFileCache;
    }

    /**
     * @throws AuthorizationFailedException
     */
    public function initialize(int $storageUid, array $driverConfiguration)
    {
        $this->driverConfiguration = $driverConfiguration;
        $this->storageUid = $storageUid;
        $this->cantoCacheTag = sprintf(self::CANTO_CACHE_TAG_BLUEPRINT, $this->storageUid);
        $this->client = $this->buildCantoClient();
        $this->authenticateAgainstCanto();
    }

    public function getCantoCacheTag(): string
    {
        return $this->cantoCacheTag;
    }

    public function setSessionTokenValid(int $sessionTokenValid): void
    {
        $this->sessionTokenValid = $sessionTokenValid;
    }

    /**
     * @throws FolderDoesNotExistException
     */
    public function getFolderDetails(string $scheme, string $folderIdentifier): array
    {
        $combinedIdentifier = CantoUtility::buildCombinedIdentifier($scheme, $folderIdentifier);
        $cacheIdentifier = $this->buildValidCacheIdentifier($combinedIdentifier);
        if ($this->cantoFolderCache->has($cacheIdentifier)) {
            return $this->cantoFolderCache->get($cacheIdentifier);
        }

        $request = new GetDetailsRequest($folderIdentifier, $scheme);
        try {
            $response = $this->client->libraryTree()->getDetails($request);
        } catch (NotAuthorizedException | InvalidResponseException $e) {
            throw new FolderDoesNotExistException(
                'Folder "' . $folderIdentifier . '" does not exist.',
                1626950904,
                $e
            );
        }
        $result = $response->getResponseData();
        $this->setFolderCache($combinedIdentifier, $result);
        return $result;
    }

    public function setFolderCache(string $folderIdentifier, array $result): void
    {
        $cacheIdentifier = $this->buildValidCacheIdentifier($folderIdentifier);
        if (!$this->cantoFolderCache->has($cacheIdentifier)) {
            $this->cantoFolderCache->set(
                $cacheIdentifier,
                $result,
                [$this->cantoCacheTag]
            );
        }
    }

    public function getFileDetails(string $scheme, string $fileIdentifier): ?array
    {
        $combinedIdentifier = CantoUtility::buildCombinedIdentifier($scheme, $fileIdentifier);
        $cacheIdentifier = $this->buildValidCacheIdentifier($combinedIdentifier);
        if ($this->cantoFileCache->has($cacheIdentifier)) {
            return $this->cantoFileCache->get($cacheIdentifier);
        }

        $request = new GetContentDetailsRequest($fileIdentifier, $scheme);
        try {
            $response = $this->client->asset()->getContentDetails($request);
        } catch (NotAuthorizedException | InvalidResponseException $e) {
            return null;
        }
        $result = $response->getResponseData();
        $this->setFileCache($combinedIdentifier, $result);

        return $result;
    }

    public function setFileCache(string $fileIdentifier, array $result): void
    {
        $cacheIdentifier = $this->buildValidCacheIdentifier($fileIdentifier);
        if (!$this->cantoFileCache->has($cacheIdentifier)) {
            $this->cantoFileCache->set(
                $cacheIdentifier,
                $result,
                [$this->cantoCacheTag]
            );
        }
    }

    public function getFileForLocalProcessing(string $fileIdentifier, bool $preview = false): string
    {
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($fileIdentifier);
        $identifier = CantoUtility::getIdFromCombinedIdentifier($fileIdentifier);
        $fileData = $this->getFileDetails($scheme, $identifier);
        $sourcePath = $fileData['url']['directUrlOriginal'] ?? null;
        $fileExtension = PathUtility::pathinfo($fileData['name'], PATHINFO_EXTENSION);
        if ($preview) {
            $sourcePath = $fileData['url']['preview'] ?? null;
            $fileExtension = 'jpg';
        }
        if ($sourcePath === null) {
            throw new \RuntimeException(
                sprintf('Getting original url for file %s failed.', $fileIdentifier),
                1627391514
            );
        }
        $temporaryPath = GeneralUtility::tempnam('canto_clone_', '.' . $fileExtension);
        try {
            $fileContent = $this->client
                ->asset()
                ->getAuthorizedUrlContent($sourcePath)
                ->getBody()
                ->getContents();
        } catch (NotAuthorizedException | InvalidResponseException $e) {
            throw new \RuntimeException(
                sprintf('Getting original fiule content for file %s failed.', $fileIdentifier),
                1627549128
            );
        }
        file_put_contents($temporaryPath, $fileContent);
        touch($temporaryPath, CantoUtility::buildTimestampFromCantoDate($fileData['default']['Date modified']));
        if (!file_exists($temporaryPath)) {
            throw new \RuntimeException(
                'Copying file "' . $fileIdentifier . '" to temporary path "' . $temporaryPath . '" failed.',
                1320577649
            );
        }
        return $temporaryPath;
    }

    public function getFilesInFolder(
        string $folderIdentifier,
        int $start,
        int $limit,
        string $sortBy = ListAlbumContentRequest::SORT_BY_TIME,
        string $sortDirection = ListAlbumContentRequest::SORT_DIRECTION_ASC
    ): array {
        $request = new ListAlbumContentRequest($folderIdentifier);
        $request->setSortBy($sortBy)
            ->setSortDirection($sortDirection)
            ->setLimit($limit)
            ->setStart($start);
        try {
            $response = $this->client->libraryTree()->listAlbumContent($request);
        } catch (InvalidResponseException | NotAuthorizedException $e) {
            return [];
        }
        return $response->getResults();
    }

    public function countFilesInFolder(string $folderIdentifier): int
    {
        $request = new ListAlbumContentRequest($folderIdentifier);
        $request->setLimit(1);
        try {
            $response = $this->client->libraryTree()->listAlbumContent($request);
        } catch (InvalidResponseException | NotAuthorizedException $e) {
            return 0;
        }
        return $response->getFound();
    }

    public function getFolderIdentifierTree(string $sortBy, string $sortDirection): array
    {
        $treeIdentifier = sha1($this->storageUid . $sortBy . $sortDirection);
        $cacheIdentifier = sprintf('fulltree_%s', $treeIdentifier);
        if (!$this->cantoFolderCache->has($cacheIdentifier)) {
            try {
                $folderIdentifier = '';
                if ($this->driverConfiguration['rootFolderScheme'] === CantoUtility::SCHEME_FOLDER
                    && $this->driverConfiguration['rootFolder'] !== '') {
                    $folderIdentifier = $this->driverConfiguration['rootFolder'];
                }
                $response = $this->client->libraryTree()->getTree(new GetTreeRequest($folderIdentifier));
                $folderTree = $this->buildFolderTree($response->getResults());
                $this->cantoFolderCache->set(
                    $cacheIdentifier,
                    $folderTree,
                    [$this->cantoCacheTag]
                );
            } catch (NotAuthorizedException | InvalidResponseException $e) {
                return [];
            }
        }
        return $this->cantoFolderCache->get($cacheIdentifier);
    }

    protected function buildFolderTree(array $treeItems): array
    {
        $folderIdentifiers = [];
        foreach ($treeItems as $folder) {
            $folderIdentifier = CantoUtility::buildCombinedIdentifier($folder['scheme'], $folder['id']);
            $folderIdentifiers[$folderIdentifier] = $this->buildFolderTree($folder['children'] ?? []);
            $this->setFolderCache($folderIdentifier, $folder);
        }
        return $folderIdentifiers;
    }

    protected function buildValidCacheIdentifier(string $cacheIdentifier): string
    {
        return sha1($cacheIdentifier);
    }

    /**
     * @throws AuthorizationFailedException
     */
    protected function authenticateAgainstCanto(): void
    {
        $accessTokenValidKey = sprintf('accessTokenForStorage%sValidUntil', $this->storageUid);
        $accessTokenKey = sprintf('accessTokenForStorage%s', $this->storageUid);
        $accessTokenValid = $this->registry->get(self::REGISTRY_NAMESPACE, $accessTokenValidKey, 0);
        $accessToken = $this->registry->get(self::REGISTRY_NAMESPACE, $accessTokenKey);
        $now = (new \DateTime())->getTimestamp();

        if ($accessToken === null || $accessTokenValid < $now) {
            $accessToken = $this->client
                ->authorizeWithClientCredentials(
                    $this->driverConfiguration['userId'] ?? '',
                    $this->driverConfiguration['scope'] ?? OAuth2Request::SCOPE_ADMIN
                )
                ->getAccessToken();
            $this->registry->set(self::REGISTRY_NAMESPACE, $accessTokenKey, $accessToken);
            $this->registry->set(
                self::REGISTRY_NAMESPACE,
                $accessTokenValidKey,
                $now + $this->sessionTokenValid
            );
        }
        $this->client->setAccessToken($accessToken);
    }

    protected function buildCantoClient(): Client
    {
        /** @var CantoClientFactory $cantoClientFactory */
        $cantoClientFactory = GeneralUtility::makeInstance(CantoClientFactory::class);
        return $cantoClientFactory->createClientFromDriverConfiguration($this->driverConfiguration);
    }
}

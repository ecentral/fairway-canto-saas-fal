<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Driver;

use Ecentral\CantoSaasApiClient\Client;
use Ecentral\CantoSaasApiClient\Endpoint\Authorization\AuthorizationFailedException;
use Ecentral\CantoSaasApiClient\Endpoint\Authorization\NotAuthorizedException;
use Ecentral\CantoSaasApiClient\Http\Asset\GetContentDetailsRequest;
use Ecentral\CantoSaasApiClient\Http\InvalidResponseException;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\GetDetailsRequest;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\GetTreeRequest;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\ListAlbumContentRequest;
use Ecentral\CantoSaasApiClient\Http\LibraryTree\SearchFolderRequest;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CantoDriver extends AbstractReadOnlyDriver
{
    const SCHEME_FOLDER = 'folder';
    const SCHEME_ALBUM = 'album';
    const ROOT_FOLDER = 'ROOT';

    const FILE_CACHE_IDENTIFIER = 'file_%s';
    const FOLDER_CACHE_IDENTIFIER = 'folder_%s';

    const FAKE_FOLDER_IDENTIFIER = 'FAKEFOLDER';

    protected Client $cantoClient;

    protected Registry $registry;

    protected FrontendInterface $cantoFolderCache;

    protected FrontendInterface $cantoFileCache;

    /**
     * The session token is valid for 30 days.
     * This property contains the time in seconds, until the token should be renewed.
     * Default: 29 days
     */
    protected int $sessionTokenValid = 2505600;

    protected string $rootFolderIdentifier;

    public function __construct(array $configuration = [])
    {
        parent::__construct($configuration);
        $this->registry = GeneralUtility::makeInstance(Registry::class);
        $this->capabilities = ResourceStorage::CAPABILITY_BROWSABLE;
        $this->cantoFolderCache = GeneralUtility::getContainer()->get('cache.canto_folder');
        $this->cantoFileCache = GeneralUtility::getContainer()->get('cache.canto_file');
        $this->rootFolderIdentifier = $this->buildCombinedIdentifier(self::SCHEME_FOLDER, self::ROOT_FOLDER);
    }

    /**
     * Processes the configuration for this driver.
     */
    public function processConfiguration()
    {
        $this->cantoClient = $this->buildCantoClient();
        $this->authenticateAgainstCanto();
    }

    /**
     * Needs to sty because of interface.
     */
    public function initialize()
    {
    }

    /**
     * Merges the capabilities merged by the user at the storage
     * configuration into the actual capabilities of the driver
     * and returns the result.
     *
     * @param int $capabilities
     * @return int
     */
    public function mergeConfigurationCapabilities($capabilities)
    {
        $this->capabilities &= $capabilities;
        return $this->capabilities;
    }

    /**
     * Returns the Identifier of the root level folder of the storage.
     *
     * @return string
     */
    public function getRootLevelFolder()
    {
        return $this->rootFolderIdentifier;
    }

    /**
     * Returns identifier of the default folder new files should be put into.
     *
     * @return string
     */
    public function getDefaultFolder()
    {
        return $this->rootFolderIdentifier;
    }

    /**
     * Returns the identifier of the folder the file resides in
     *
     * @param string $fileIdentifier
     * @return string
     */
    public function getParentFolderIdentifierOfIdentifier($fileIdentifier)
    {
        $scheme = $this->getSchemeFromCombinedIdentifier($fileIdentifier);
        $explicitFileIdentifier = $this->getIdFromCombinedIdentifier($fileIdentifier);
        if ($explicitFileIdentifier === self::ROOT_FOLDER) {
            return $fileIdentifier;
        }

        $result = $this->getFolderDetails($scheme, $explicitFileIdentifier);
        $pathIds = explode('/', $result['idPath']);
        if (count($pathIds) === 1) {
            return $this->rootFolderIdentifier;
        }

        // Remove current folder/album id.
        array_pop($pathIds);
        // The parent folder is always of scheme folder because albums can only contain files.
        return $this->buildCombinedIdentifier(self::SCHEME_FOLDER, array_pop($pathIds));
    }

    /**
     * Returns the public URL to a file.
     * Either fully qualified URL or relative to public web path (rawurlencoded).
     *
     * @param string $identifier
     * @return string|null NULL if file is missing or deleted, the generated url otherwise
     */
    public function getPublicUrl($identifier)
    {
        // TODO: Implement getPublicUrl() method.
        return '';
    }

    /**
     * Checks if a file exists.
     *
     * @param string $fileIdentifier
     * @return bool
     */
    public function fileExists($fileIdentifier)
    {
        $scheme = $this->getSchemeFromCombinedIdentifier($fileIdentifier);
        $explicitFileIdentifier = $this->getIdFromCombinedIdentifier($fileIdentifier);
        // TODO check why there are folder identifiers handed to this method.
        if ($scheme === self::SCHEME_FOLDER || $scheme === self::SCHEME_ALBUM) {
            return $this->folderExists($fileIdentifier);
        }
        $result = $this->getFileDetails($scheme, $explicitFileIdentifier);
        return !empty($result);
    }

    /**
     * Checks if a folder exists.
     *
     * @param string $folderIdentifier
     * @return bool
     */
    public function folderExists($folderIdentifier)
    {
        $scheme = $this->getSchemeFromCombinedIdentifier($folderIdentifier);
        $explicitFolderIdentifier = $this->getIdFromCombinedIdentifier($folderIdentifier);
        if ($explicitFolderIdentifier === self::ROOT_FOLDER) {
            return true;
        }

        try {
            $result = $this->getFolderDetails($scheme, $explicitFolderIdentifier);
        } catch (FolderDoesNotExistException $e) {
            return false;
        }
        return !empty($result);
    }

    /**
     * Checks if a folder contains files and (if supported) other folders.
     *
     * @param string $folderIdentifier
     * @return bool TRUE if there are no files and folders within $folder
     */
    public function isFolderEmpty($folderIdentifier)
    {
        return ($this->countFilesInFolder($folderIdentifier) + $this->countFoldersInFolder($folderIdentifier)) === 0;
    }

    /**
     * Creates a hash for a file.
     *
     * @param string $fileIdentifier
     * @param string $hashAlgorithm The hash algorithm to use
     * @return string
     */
    public function hash($fileIdentifier, $hashAlgorithm)
    {
        return hash($hashAlgorithm, $fileIdentifier);
    }

    /**
     * Returns the contents of a file. Beware that this requires to load the
     * complete file into memory and also may require fetching the file from an
     * external location. So this might be an expensive operation (both in terms
     * of processing resources and money) for large files.
     *
     * @param string $fileIdentifier
     * @return string The file contents
     */
    public function getFileContents($fileIdentifier)
    {
        // TODO: Implement getFileContents() method.
        return '';
    }

    /**
     * Checks if a file inside a folder exists
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return bool
     */
    public function fileExistsInFolder($fileName, $folderIdentifier)
    {
        // TODO: Implement fileExistsInFolder() method.
        return true;
    }

    /**
     * Checks if a folder inside a folder exists.
     *
     * @param string $folderName
     * @param string $folderIdentifier
     * @return bool
     */
    public function folderExistsInFolder($folderName, $folderIdentifier)
    {
        // TODO: Implement folderExistsInFolder() method.
        return true;
    }

    /**
     * Returns a path to a local copy of a file for processing it. When changing the
     * file, you have to take care of replacing the current version yourself!
     *
     * @param string $fileIdentifier
     * @param bool $writable Set this to FALSE if you only need the file for read
     *                       operations. This might speed up things, e.g. by using
     *                       a cached local version. Never modify the file if you
     *                       have set this flag!
     * @return string The path to the file on the local disk
     */
    public function getFileForLocalProcessing($fileIdentifier, $writable = true)
    {
        // TODO: Implement getFileForLocalProcessing() method.
        return '';
    }

    /**
     * Returns the permissions of a file/folder as an array
     * (keys r, w) of boolean flags
     *
     * @param string $identifier
     * @return array
     */
    public function getPermissions($identifier)
    {
        return [
            'r' => true,
            'w' => false,
        ];
    }

    /**
     * Directly output the contents of the file to the output
     * buffer. Should not take care of header files or flushing
     * buffer before. Will be taken care of by the Storage.
     *
     * @param string $identifier
     */
    public function dumpFileContents($identifier)
    {
        // TODO: Implement dumpFileContents() method.
    }

    /**
     * Checks if a given identifier is within a container, e.g. if
     * a file or folder is within another folder.
     * This can e.g. be used to check for web-mounts.
     *
     * Hint: this also needs to return TRUE if the given identifier
     * matches the container identifier to allow access to the root
     * folder of a filemount.
     *
     * @param string $folderIdentifier
     * @param string $identifier identifier to be checked against $folderIdentifier
     * @return bool TRUE if $content is within or matches $folderIdentifier
     */
    public function isWithin($folderIdentifier, $identifier)
    {
        try {
            $schemeToCheck = $this->getSchemeFromCombinedIdentifier($folderIdentifier);
        } catch (\InvalidArgumentException $e) {
            /*
             * This exception is catched, because the processing folder is currently handed
             * to this method, even it is configured for another driver.
             * See https://forge.typo3.org/issues/94645
             */
            return false;
        }

        if ($schemeToCheck === self::SCHEME_FOLDER || $schemeToCheck === self::SCHEME_ALBUM) {
            return $this->folderExistsInFolder($folderIdentifier, $identifier);
        }

        return $this->fileExistsInFolder($folderIdentifier, $identifier);
    }

    /**
     * Returns information about a file.
     *
     * @param string $fileIdentifier
     * @param array $propertiesToExtract Array of properties which are be extracted
     *                                   If empty all will be extracted
     * @return array
     */
    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = [])
    {
        $scheme = $this->getSchemeFromCombinedIdentifier($fileIdentifier);
        $explicitFileIdentifier = $this->getIdFromCombinedIdentifier($fileIdentifier);
        $result = $this->getFileDetails($scheme, $explicitFileIdentifier);

        // TODO Check why folder and album identifiers are handed to this method.
        if ($scheme === self::SCHEME_FOLDER || $scheme === self::SCHEME_ALBUM) {
            return $this->getFolderInfoByIdentifier($fileIdentifier);
        }

        return [
            'size' => $result['default']['Size'],
            'atime' => time(),
            'mtime' => $this->buildTimestampFromCantoDate($result['default']['Date modified']),
            'ctime' => $this->buildTimestampFromCantoDate($result['default']['Date uploaded']),
            'mimetype' => $result['default']['Content Type'] ?? '',
            'name' => $result['name'],
            'extension' => pathinfo($result['name'], PATHINFO_EXTENSION),
            'identifier' => $fileIdentifier,
            'identifier_hash' => $this->hashIdentifier($fileIdentifier),
            'storage' => $this->storageUid,
            'folder_hash' => $this->hashIdentifier($this->buildCombinedIdentifier(self::FAKE_FOLDER_IDENTIFIER, self::SCHEME_ALBUM)),
        ];
    }

    /**
     * Returns information about a folder.
     *
     * @param string $folderIdentifier In the case of the LocalDriver, this is the (relative) path to the file.
     * @return array
     * @throws FolderDoesNotExistException
     */
    public function getFolderInfoByIdentifier($folderIdentifier)
    {
        $explicitFolderIdentifier = $this->getIdFromCombinedIdentifier($folderIdentifier);
        $scheme = $this->getSchemeFromCombinedIdentifier($folderIdentifier);
        if ($explicitFolderIdentifier === self::ROOT_FOLDER) {
            $now = time();
            return [
                'identifier' => $folderIdentifier,
                'name' => 'Canto',
                'mtime' => $now,
                'ctime' => $now,
                'storage' => $this->storageUid
            ];
        }
        $result = $this->getFolderDetails($scheme, $explicitFolderIdentifier);
        // TODO Find solution how to handle equal folder and album names.
        $folderName = sprintf('F: %s', $result['name']);
        if ($scheme === self::SCHEME_ALBUM) {
            $folderName = sprintf('A: %s', $result['name']);
        }

        return [
            'identifier' => $folderIdentifier,
            'name' => $folderName,
            'mtime' => $this->buildTimestampFromCantoDate($result['time']),
            'ctime' => $this->buildTimestampFromCantoDate($result['created']),
            'storage' => $this->storageUid
        ];
    }

    /**
     * Returns the identifier of a file inside the folder
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return string file identifier
     */
    public function getFileInFolder($fileName, $folderIdentifier)
    {
        // TODO: Implement getFileInFolder() method.
        return $fileName;
    }

    /**
     * Returns a list of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array of FileIdentifiers
     */
    public function getFilesInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $filenameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ) {
        $scheme = $this->getSchemeFromCombinedIdentifier($folderIdentifier);
        $explicitFolderIdentifier = $this->getIdFromCombinedIdentifier($folderIdentifier);
        if ($scheme === self::SCHEME_FOLDER || $explicitFolderIdentifier === self::ROOT_FOLDER) {
            // There are no files in folders, just other files and albums.
            return [];
        }

        $files = [];
        $sortBy = $this->mapSortBy($sort);
        $sortDirection = $sortRev ? ListAlbumContentRequest::SORT_DIRECTION_DESC
            : ListAlbumContentRequest::SORT_DIRECTION_ASC;
        $limit = min($numberOfItems, 100);
        $request = new ListAlbumContentRequest($explicitFolderIdentifier);
        $request->setSortBy($sortBy)
            ->setSortDirection($sortDirection)
            ->setLimit($limit)
            ->setStart($start);
        try {
            $response = $this->cantoClient->libraryTree()->listAlbumContent($request);
        } catch (InvalidResponseException | NotAuthorizedException $e) {
            return [];
        }

        foreach ($response->getResults() as $result) {
            $fileIdentifier = $this->buildCombinedIdentifier($result['scheme'], $result['id']);
            $cacheIdentifier = $this->buildValidCacheIdentifier(
                sprintf(self::FILE_CACHE_IDENTIFIER, $fileIdentifier)
            );
            $this->setFileCache($cacheIdentifier, $result);
            $files[] = $fileIdentifier;
        }
        return $files;
    }

    /**
     * Returns the identifier of a folder inside the folder
     *
     * @param string $folderName The name of the target folder
     * @param string $folderIdentifier
     * @return string folder identifier
     */
    public function getFolderInFolder($folderName, $folderIdentifier)
    {
        // TODO: Implement getFolderInFolder() method.
        return $folderName;
    }

    /**
     * Returns a list of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks The method callbacks to use for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array of Folder Identifier
     */
    public function getFoldersInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $folderNameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ) {
        $scheme = $this->getSchemeFromCombinedIdentifier($folderIdentifier);
        $explicitFolderIdentifier = $this->getIdFromCombinedIdentifier($folderIdentifier);
        if ($scheme === self::SCHEME_ALBUM) {
            // Albums contain only files, not folders.
            return [];
        }
        if ($explicitFolderIdentifier === self::ROOT_FOLDER) {
            $explicitFolderIdentifier = '';
        }

        $folders = [];
        $sortBy = GetTreeRequest::SORT_BY_NAME;
        $sortDirection = $sortRev ? GetTreeRequest::SORT_DIRECTION_DESC : GetTreeRequest::SORT_DIRECTION_ASC;
        $layer = $recursive ? -1 : 1;
        $request = new GetTreeRequest($explicitFolderIdentifier);
        $request->setLayer($layer)
            ->setSortBy($sortBy)
            ->setSortDirection($sortDirection);
        try {
            $response = $this->cantoClient->libraryTree()->getTree($request);
        } catch (NotAuthorizedException | InvalidResponseException $e) {
            return [];
        }

        // $c is the counter for how many items we still have to fetch (-1 is unlimited)
        $c = $numberOfItems > 0 ? $numberOfItems : - 1;
        foreach ($response->getResults() as $result) {
            if ($c === 0) {
                break;
            }
            if ($start > 0) {
                $start--;
            } else {
                $currentFolderIdentifier = $this->buildCombinedIdentifier($result['scheme'], $result['id']);
                $folders[$currentFolderIdentifier] = $currentFolderIdentifier;
                $cacheIdentifier = $this->buildValidCacheIdentifier(
                    sprintf(self::FOLDER_CACHE_IDENTIFIER, $currentFolderIdentifier)
                );
                $this->setFolderCache($cacheIdentifier, $result);
                --$c;
            }
        }
        return $folders;
    }

    /**
     * Returns the number of files inside the specified path
     *
     * @param string  $folderIdentifier
     * @param bool $recursive
     * @param array   $filenameFilterCallbacks callbacks for filtering the items
     * @return int Number of files in folder
     */
    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = [])
    {
        $scheme = $this->getSchemeFromCombinedIdentifier($folderIdentifier);
        $explicitFolderIdentifier = $this->getIdFromCombinedIdentifier($folderIdentifier);
        if ($scheme === self::SCHEME_FOLDER || $explicitFolderIdentifier === self::ROOT_FOLDER) {
            // Folders can not have files, just other folders and albums.
            return 0;
        }

        $request = new ListAlbumContentRequest($explicitFolderIdentifier);
        $request->setLimit(1);
        try {
            $response = $this->cantoClient->libraryTree()->listAlbumContent($request);
        } catch (InvalidResponseException | NotAuthorizedException $e) {
            return 0;
        }

        return $response->getFound();
    }

    /**
     * Returns the number of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks callbacks for filtering the items
     * @return int Number of folders in folder
     */
    public function countFoldersInFolder($folderIdentifier, $recursive = false, array $folderNameFilterCallbacks = [])
    {
        $scheme = $this->getSchemeFromCombinedIdentifier($folderIdentifier);
        $explicitFolderIdentifier = $this->getIdFromCombinedIdentifier($folderIdentifier);
        if ($scheme === self::SCHEME_ALBUM) {
            // albums can not contain folders.
            return 0;
        }
        if ($explicitFolderIdentifier === self::ROOT_FOLDER) {
            $explicitFolderIdentifier = '';
        }

        $request = new GetTreeRequest($explicitFolderIdentifier);
        $request->setLayer($recursive ? -1 : 1)
            ->setSortBy(GetTreeRequest::SORT_BY_NAME)
            ->setSortDirection(GetTreeRequest::SORT_DIRECTION_ASC);
        try {
            $response = $this->cantoClient->libraryTree()->getTree($request);
        } catch (NotAuthorizedException | InvalidResponseException $e) {
            return 0;
        }

        return count($response->getResults());
    }

    /**
     * Hashes a file identifier, taking the case sensitivity of the file system
     * into account. This helps mitigating problems with case-insensitive
     * databases.
     *
     * @param string $identifier
     * @return string
     */
    public function hashIdentifier($identifier)
    {
        $scheme = $this->getSchemeFromCombinedIdentifier($identifier);
        if ($scheme === self::SCHEME_ALBUM || $scheme === self::SCHEME_FOLDER) {
            $identifier = $this->canonicalizeAndCheckFolderIdentifier($identifier);
        }
        $identifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
        return $this->hash($identifier, 'sha1');
    }

    protected function buildValidCacheIdentifier(string $cacheIdentifier): string
    {
        return $this->hash($cacheIdentifier, 'sha1');
    }

    /**
     * @throws FolderDoesNotExistException
     */
    protected function getFolderDetails(string $scheme, string $folderIdentifier): array
    {
        $combinedIdentifier = $this->buildCombinedIdentifier($scheme, $folderIdentifier);
        $cacheIdentifier = $this->buildValidCacheIdentifier(
            sprintf(self::FOLDER_CACHE_IDENTIFIER, $combinedIdentifier)
        );
        if ($this->cantoFolderCache->has($cacheIdentifier)) {
            return $this->cantoFolderCache->get($cacheIdentifier);
        }

        $request = new GetDetailsRequest($folderIdentifier, $scheme);
        try {
            $response = $this->cantoClient->libraryTree()->getDetails($request);
        } catch (NotAuthorizedException | InvalidResponseException $e) {
            throw new FolderDoesNotExistException(
                'Folder "' . $folderIdentifier . '" does not exist.',
                1626950904,
                $e
            );
        }
        $result = $response->getResponseData();
        $this->setFolderCache($cacheIdentifier, $result);
        return $result;
    }

    protected function setFolderCache(string $cacheIdentifier, array $result): void
    {
        if (!$this->cantoFolderCache->has($cacheIdentifier)) {
            $this->cantoFolderCache->set($cacheIdentifier, $result);
        }
    }

    protected function getFileDetails(string $scheme, string $fileIdentifier): ?array
    {
        $combinedIdentifier = $this->buildCombinedIdentifier($scheme, $fileIdentifier);
        $cacheIdentifier = $this->buildValidCacheIdentifier(sprintf(self::FILE_CACHE_IDENTIFIER, $combinedIdentifier));
        if ($this->cantoFileCache->has($cacheIdentifier)) {
            return $this->cantoFileCache->get($cacheIdentifier);
        }

        $request = new GetContentDetailsRequest($fileIdentifier, $scheme);
        try {
            $response = $this->cantoClient->asset()->getContentDetails($request);
        } catch (NotAuthorizedException | InvalidResponseException $e) {
            return null;
        }
        $result = $response->getResponseData();
        $this->setFileCache($cacheIdentifier, $result);

        return $result;
    }

    protected function setFileCache(string $cacheIdentifier, array $result): void
    {
        if (!$this->cantoFileCache->has($cacheIdentifier)) {
            $this->cantoFileCache->set($cacheIdentifier, $result);
        }
    }

    /**
     * @throws AuthorizationFailedException
     */
    protected function authenticateAgainstCanto(): void
    {
        $accessTokenValid = $this->registry->get('cantoSaasFal', 'accessTokenValidUntil', 0);
        $accessToken = $this->registry->get('cantoSaasFal', 'accessToken');
        $now = (new \DateTime())->getTimestamp();

        if ($accessToken === null || $accessTokenValid < $now) {
            $accessToken = $this->cantoClient
                ->authorizeWithClientCredentials($this->configuration['userId'])
                ->getAccessToken();
            $this->registry->set('cantoSaasFal', 'accessToken', $accessToken);
            $this->registry->set(
                'cantoSaasFal',
                'accessTokenValidUntil',
                $now + $this->sessionTokenValid
            );
        }
        $this->cantoClient->setAccessToken($accessToken);
    }

    protected function invalidateAccessToken(): void
    {
        $this->registry->remove('cantoSaasFal', 'accessToken');
    }

    protected function buildCantoClient(): Client
    {
        /** @var CantoClientFactory $cantoClientFactory */
        $cantoClientFactory = GeneralUtility::makeInstance(CantoClientFactory::class);
        return $cantoClientFactory->createClientFromDriverConfiguration($this->configuration);
    }

    protected function buildCombinedIdentifier(string $scheme, string $id): string
    {
        return sprintf('%s|%s', $scheme, $id);
    }

    /**
     * @throw \InvalidArgumentException
     */
    protected function getSchemeFromCombinedIdentifier(string $combinedIdentifier): string
    {
        $identifierParts = explode('|', $combinedIdentifier);
        if (count($identifierParts) !== 2) {
            throw new \InvalidArgumentException(
                'Invalid combined identifier given.',
                1626954151
            );
        }
        return $identifierParts[0];
    }

    /**
     * @throw \InvalidArgumentException
     */
    protected function getIdFromCombinedIdentifier(string $combinedIdentifier): string
    {
        $identifierParts = explode('|', $combinedIdentifier);
        if (count($identifierParts) !== 2) {
            throw new \InvalidArgumentException(
                'Invalid combined identifier given.',
                1626954176
            );
        }
        return $identifierParts[1];
    }

    protected function buildTimestampFromCantoDate(string $cantoDate): int
    {
        $dateTime = \DateTime::createFromFormat('YmdHisv', $cantoDate);
        return $dateTime->getTimestamp();
    }

    protected function mapSortBy(string $sortBy): string
    {
        switch ($sortBy) {
            case 'name':
                return SearchFolderRequest::SORT_BY_NAME;
            case 'fileext':
                return SearchFolderRequest::SORT_BY_SCHEME;
            case 'size':
                return SearchFolderRequest::SORT_BY_SIZE;
        }
        return SearchFolderRequest::SORT_BY_TIME;
    }

    /**
     * Makes sure the path given as parameter is valid
     *
     * @param string $filePath The file path (most times filePath)
     * @return string
     */
    protected function canonicalizeAndCheckFilePath($filePath)
    {
        return $filePath;
    }

    /**
     * Makes sure the identifier given as parameter is valid
     *
     * @param string $fileIdentifier The file Identifier
     * @return string
     * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidPathException
     */
    protected function canonicalizeAndCheckFileIdentifier($fileIdentifier)
    {
        return $fileIdentifier;
    }

    /**
     * This driver store fake folder identifiers for files because a single file can be in
     * multiple folders. To avoid getting multiple file objects, the files folder path will
     * always be the same.
     * The file identifiers are unique in each canto installation.
     *
     * @param string $folderIdentifier The folder identifier
     * @return string
     */
    protected function canonicalizeAndCheckFolderIdentifier($folderIdentifier)
    {
        $scheme = $this->getSchemeFromCombinedIdentifier($folderIdentifier);
        return $this->buildCombinedIdentifier($scheme, self::FAKE_FOLDER_IDENTIFIER);
    }
}

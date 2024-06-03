<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Resource\Driver;

use Fairway\CantoSaasApi\DTO\Status;
use Fairway\CantoSaasApi\Endpoint\Authorization\AuthorizationFailedException;
use Fairway\CantoSaasApi\Endpoint\Authorization\NotAuthorizedException;
use Fairway\CantoSaasApi\Http\Asset\BatchDeleteContentRequest;
use Fairway\CantoSaasApi\Http\Asset\RenameContentRequest;
use Fairway\CantoSaasApi\Http\InvalidResponseException;
use Fairway\CantoSaasApi\Http\LibraryTree\CreateAlbumFolderRequest;
use Fairway\CantoSaasApi\Http\LibraryTree\DeleteFolderOrAlbumRequest;
use Fairway\CantoSaasApi\Http\LibraryTree\GetTreeRequest;
use Fairway\CantoSaasApi\Http\LibraryTree\ListAlbumContentRequest;
use Fairway\CantoSaasApi\Http\LibraryTree\SearchFolderRequest;
use Fairway\CantoSaasApi\Http\Upload\GetUploadSettingRequest;
use Fairway\CantoSaasApi\Http\Upload\QueryUploadStatusRequest;
use Fairway\CantoSaasApi\Http\Upload\UploadFileRequest;
use Fairway\CantoSaasFal\Resource\MdcUrlGenerator;
use Fairway\CantoSaasFal\Resource\Repository\CantoRepository;
use Fairway\CantoSaasFal\Utility\CantoUtility;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use TYPO3\CMS\Core\Http\FalDumpFileContentsDecoratorStream;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Driver\StreamableDriverInterface;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class CantoDriver extends AbstractDriver implements StreamableDriverInterface
{
    public const DRIVER_NAME = 'Canto';

    public const ROOT_FOLDER = 'ROOT';

    protected CantoRepository $cantoRepository;

    protected string $rootFolderIdentifier;

    protected bool $validCantoConfiguration;

    /** @var string[] */
    public static array $transientCachedFiles = [];

    private ?MdcUrlGenerator $mdcUrlGenerator = null;

    public function __construct(array $configuration = [])
    {
        parent::__construct($configuration);
        $this->capabilities = ResourceStorage::CAPABILITY_BROWSABLE |
            ResourceStorage::CAPABILITY_WRITABLE;
        $this->rootFolderIdentifier = $this->buildRootFolderIdentifier();
    }

    public function processConfiguration()
    {
        $this->validCantoConfiguration = is_int($this->storageUid)
            && $this->storageUid > 0
            && ($this->configuration['cantoName'] ?? '') !== ''
            && ($this->configuration['cantoDomain'] ?? '') !== ''
            && ($this->configuration['appId'] ?? '') !== ''
            && ($this->configuration['appSecret'] ?? '') !== '';
    }

    public function initialize()
    {
        // The check is necessary to prevent an error thrown in Maintenance Admin Tool -> Remove Temporary Assets
        if ($this->validCantoConfiguration && GeneralUtility::getContainer()->has(CantoRepository::class)) {
            $this->cantoRepository = GeneralUtility::makeInstance(CantoRepository::class);
            try {
                $this->cantoRepository->initialize($this->storageUid, $this->configuration);
            } catch (AuthorizationFailedException $e) {
                echo 'Append Canto Fal Driver configuration.';
            }
        }
        $this->mdcUrlGenerator = GeneralUtility::makeInstance(MdcUrlGenerator::class);
    }

    /**
     * @param int $capabilities
     */
    public function mergeConfigurationCapabilities($capabilities): int
    {
        $this->capabilities &= $capabilities;
        return $this->capabilities;
    }

    public function getRootLevelFolder(): string
    {
        return $this->rootFolderIdentifier;
    }

    public function getDefaultFolder(): string
    {
        return $this->rootFolderIdentifier;
    }

    /**
     * @param string $fileIdentifier
     * @throws FolderDoesNotExistException
     */
    public function getParentFolderIdentifierOfIdentifier($fileIdentifier): string
    {
        if (!$fileIdentifier) {
            return '';
        }
        if ($fileIdentifier === $this->rootFolderIdentifier) {
            return $fileIdentifier;
        }

        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($fileIdentifier);
        $explicitFileIdentifier = CantoUtility::getIdFromCombinedIdentifier($fileIdentifier);
        if (CantoUtility::isFolder($scheme)) {
            $result = $this->cantoRepository->getFolderDetails($scheme, $explicitFileIdentifier);
            $pathIds = explode('/', $result['idPath']);
            if (count($pathIds) === 1) {
                return $this->rootFolderIdentifier;
            }
            // Remove current folder/album id.
            array_pop($pathIds);
            // The parent folder is always of scheme folder because albums can only contain files.
            return CantoUtility::buildCombinedIdentifier(CantoUtility::SCHEME_FOLDER, array_pop($pathIds));
        }

        // TODO Check if this method is used for files.
        return '';
    }

    /**
     * @param string $identifier
     */
    public function getPublicUrl($identifier): ?string
    {
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($identifier);
        $fileIdentifier = CantoUtility::getIdFromCombinedIdentifier($identifier);
        $useMdc = CantoUtility::isMdcActivated($this->configuration);
        $fileData = $this->cantoRepository->getFileDetails($scheme, $fileIdentifier);
        if ($useMdc && $this->mdcUrlGenerator) {
            $url = $this->cantoRepository->generateMdcUrl($fileIdentifier);
            $url .= $this->mdcUrlGenerator->addOperationToMdcUrl([
                'width' => (int)$fileData['width'],
                'height' => (int)$fileData['height'],
            ]);
            return rawurldecode($url);
        }
        // todo: add FAIRCANTO-72 here
        if (!empty($fileData['url']['directUrlOriginal'])) {
            return rawurldecode($fileData['url']['directUrlOriginal']);
        }
        return null;
    }

    /**
     * @param string $fileIdentifier
     */
    public function fileExists($fileIdentifier): bool
    {
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($fileIdentifier);
        if (CantoUtility::isFolder($scheme)) {
            return false;
        }
        $explicitFileIdentifier = CantoUtility::getIdFromCombinedIdentifier($fileIdentifier);
        $result = $this->cantoRepository->getFileDetails(
            $scheme,
            $explicitFileIdentifier
        );
        return !empty($result);
    }

    /**
     * @param string $folderIdentifier
     */
    public function folderExists($folderIdentifier): bool
    {
        if ($folderIdentifier === $this->rootFolderIdentifier) {
            return true;
        }

        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($folderIdentifier);
        $explicitFolderIdentifier = CantoUtility::getIdFromCombinedIdentifier($folderIdentifier);
        try {
            $result = $this->cantoRepository->getFolderDetails($scheme, $explicitFolderIdentifier);
        } catch (FolderDoesNotExistException $e) {
            return false;
        }
        return !empty($result);
    }

    /**
     * @param string $folderIdentifier
     * @throws FolderDoesNotExistException
     */
    public function isFolderEmpty($folderIdentifier): bool
    {
        return ($this->countFilesInFolder($folderIdentifier) + $this->countFoldersInFolder($folderIdentifier)) === 0;
    }

    /**
     * @param string $fileIdentifier
     * @param string $hashAlgorithm The hash algorithm to use
     */
    public function hash($fileIdentifier, $hashAlgorithm): string
    {
        return hash($hashAlgorithm, $fileIdentifier);
    }

    /**
     * @param string $fileIdentifier
     */
    public function getFileContents($fileIdentifier): string
    {
        $publicUrl = $this->getPublicUrl($fileIdentifier);
        if ($publicUrl !== '') {
            return GeneralUtility::getUrl($publicUrl);
        }
        return '';
    }

    /**
     * @param string $fileName
     * @param string $folderIdentifier
     */
    public function fileExistsInFolder($fileName, $folderIdentifier): bool
    {
        return $this->getFileInFolder($fileName, $folderIdentifier) !== '';
    }

    /**
     * @param string $folderName
     * @param string $folderIdentifier
     */
    public function folderExistsInFolder($folderName, $folderIdentifier): bool
    {
        if ($folderName === $folderIdentifier) {
            return true;
        }
        try {
            $parentFolderId = CantoUtility::getIdFromCombinedIdentifier($folderIdentifier);
            $request = new GetTreeRequest($parentFolderId);
            $parentFolderChildren = $this->cantoRepository
                ->getClient()
                ->libraryTree()
                ->getTree($request)
                ->getResults();
        } catch (NotAuthorizedException|InvalidResponseException $e) {
            return false;
        }

        if ($parentFolderChildren === []) {
            return false;
        }

        if (!CantoUtility::isValidCombinedIdentifier($folderName)) {
            foreach ($parentFolderChildren as $leaf) {
                if ($leaf['name'] === $folderName) {
                    return true;
                }
            }
            return false;
        }
        $folderId = CantoUtility::getIdFromCombinedIdentifier($folderName);
        foreach ($parentFolderChildren as $leaf) {
            if ($leaf['id'] === $folderId) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $fileIdentifier
     * @param bool $writable Set this to FALSE if you only need the file for read
     *                       operations. This might speed up things, e.g. by using
     *                       a cached local version. Never modify the file if you
     *                       have set this flag!
     */
    public function getFileForLocalProcessing($fileIdentifier, $writable = true): string
    {
        return $this->cantoRepository->getFileForLocalProcessing($fileIdentifier);
    }

    /**
     * @param string $identifier
     */
    public function getPermissions($identifier): array
    {
        return [
            'r' => true,
            'w' => true,
        ];
    }

    /**
     * @param string $identifier
     */
    public function dumpFileContents($identifier): void
    {
        echo $this->getFileContents($identifier);
    }

    /**
     * @param string $folderIdentifier
     * @param string $identifier identifier to be checked against $folderIdentifier
     */
    public function isWithin($folderIdentifier, $identifier): bool
    {
        /*
         * Ensure that the given identifiers are valid. Do not throw an exception,
         * because the processing folder is currently handed to this method, even
         * if it is configured for another driver.
         * See https://forge.typo3.org/issues/94645
         */
        if (
            !CantoUtility::isValidCombinedIdentifier($folderIdentifier)
            || !CantoUtility::isValidCombinedIdentifier($identifier)
        ) {
            return false;
        }

        $schemeToCheck = CantoUtility::getSchemeFromCombinedIdentifier($identifier);
        if (CantoUtility::isFolder($schemeToCheck)) {
            return $this->folderExistsInFolder($folderIdentifier, $identifier);
        }

        return $this->fileExistsInFolder($folderIdentifier, $identifier);
    }

    /**
     * @param string $fileIdentifier
     * @param array $propertiesToExtract Array of properties which are being extracted
     *                                   If empty all will be extracted
     * @throws FolderDoesNotExistException
     */
    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = []): array
    {
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($fileIdentifier);
        if (CantoUtility::isFolder($scheme)) {
            return $this->getFolderInfoByIdentifier($fileIdentifier);
        }

        $folders = [];
        $explicitFileIdentifier = CantoUtility::getIdFromCombinedIdentifier($fileIdentifier);
        $result = $this->cantoRepository->getFileDetails(
            $scheme,
            $explicitFileIdentifier
        );
        if($result == null) {
            $data = [
                'size' => 1000,
                'atime' => time(),
                'mtime' => 0,
                'ctime' =>  0,
                'mimetype' => '',
                'name' => 'fallbackimage.jpg',
                'extension' => 'jpg',
                'identifier' => $fileIdentifier,
                'identifier_hash' => $this->hashIdentifier($fileIdentifier),
                'storage' => $this->storageUid,
                'folder_hash' => '',
                'folder_identifiers' => '',
            ];

            return $data;
        }
        foreach ($result['relatedAlbums'] ?? [] as $album) {
            $folders[] = CantoUtility::buildCombinedIdentifier($album['scheme'], $album['id']);
        }
        $data = [
            'size' => $result['default']['Size'],
            'atime' => time(),
            'mtime' => CantoUtility::buildTimestampFromCantoDate($result['default']['Date modified']),
            'ctime' => CantoUtility::buildTimestampFromCantoDate($result['default']['Date uploaded']),
            'mimetype' => $result['default']['Content Type'] ?? '',
            'name' => $result['name'],
            'extension' => PathUtility::pathinfo($result['name'], PATHINFO_EXTENSION),
            'identifier' => $fileIdentifier,
            'identifier_hash' => $this->hashIdentifier($fileIdentifier),
            'storage' => $this->storageUid,
            'folder_hash' => '',
            'folder_identifiers' => $folders,
        ];
        if (!$propertiesToExtract) {
            return $data;
        }
        $properties = [];
        foreach ($propertiesToExtract as $item) {
            $properties[$item] = $data[$item];
        }
        return $properties;
    }

    /**
     * @param string $folderIdentifier In the case of the LocalDriver, this is the (relative) path to the file.
     * @throws FolderDoesNotExistException
     */
    public function getFolderInfoByIdentifier($folderIdentifier): array
    {
        $now = time();
        $rootFolder = [
            'identifier' => $this->rootFolderIdentifier,
            'name' => 'Canto',
            'mtime' => $now,
            'ctime' => $now,
            'storage' => $this->storageUid
        ];
        if (!$folderIdentifier || $folderIdentifier === $this->rootFolderIdentifier) {
            return $rootFolder;
        }
        $explicitFolderIdentifier = CantoUtility::getIdFromCombinedIdentifier($folderIdentifier);
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($folderIdentifier);
        $result = $this->cantoRepository->getFolderDetails($scheme, $explicitFolderIdentifier);
        // TODO Find solution how to handle equal folder and album names.
        $folderName = sprintf('F: %s', $result['name']);
        if ($scheme === CantoUtility::SCHEME_ALBUM) {
            $folderName = sprintf('A: %s', $result['name']);
        }

        return [
            'identifier' => $folderIdentifier,
            'name' => $folderName,
            'mtime' => CantoUtility::buildTimestampFromCantoDate($result['time']),
            'ctime' => CantoUtility::buildTimestampFromCantoDate($result['created']),
            'storage' => $this->storageUid
        ];
    }

    /**
     * @param string $fileName
     * @param string $folderIdentifier
     */
    public function getFileInFolder($fileName, $folderIdentifier): string
    {
        $filesWithName = $this->resolveFilesInFolder(
            $folderIdentifier,
            0,
            0,
            false,
            [],
        ) ?? [];
        foreach ($filesWithName as $file) {
            if ($file['name'] === $fileName) {
                return $file['id'];
            }
        }
        return '';
    }

    /**
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
     */
    public function getFilesInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $filenameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ): array {
        $files = [];
        $results = $this->resolveFilesInFolder($folderIdentifier, $start, $numberOfItems, $recursive, $filenameFilterCallbacks, $sort, $sortRev);
        foreach ($results as $result) {
            $fileIdentifier = CantoUtility::buildCombinedIdentifier($result['scheme'], $result['id']);
            $this->cantoRepository->setFileCache($fileIdentifier, $result);
            $files[] = $fileIdentifier;
        }
        return $files;
    }

    protected function resolveFilesInFolder(
        string $folderIdentifier,
        int $start = 0,
        int $numberOfItems = 0,
        bool $recursive = false,
        array $filenameFilterCallbacks = [],
        string $sort = '',
        bool $sortRev = false
    ) {
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($folderIdentifier);
        if ($scheme === CantoUtility::SCHEME_FOLDER || $folderIdentifier === $this->rootFolderIdentifier) {
            // There are no files in folders, just other files and albums.
            return [];
        }

        $explicitFolderIdentifier = CantoUtility::getIdFromCombinedIdentifier($folderIdentifier);
        $sortBy = $this->mapSortBy($sort);
        $sortDirection = $sortRev ? ListAlbumContentRequest::SORT_DIRECTION_DESC
            : ListAlbumContentRequest::SORT_DIRECTION_ASC;
        $limit = $numberOfItems > 0 ? min($numberOfItems, 1000) : 1000;
        // TODO Check if there are more that 1000 files and make multiple requests if needed.
        return $this->cantoRepository->getFilesInFolder(
            $explicitFolderIdentifier,
            $start,
            $limit,
            $sortBy,
            $sortDirection
        );
    }

    /**
     * @param string $folderName The name of the target folder
     * @param string $folderIdentifier
     */
    public function getFolderInFolder($folderName, $folderIdentifier): string
    {
        $foldersWithName = $this->getFoldersInFolder(
            $folderIdentifier,
            0,
            0,
            false,
            [$folderIdentifier],
        ) ?? [];
        if (count($foldersWithName) !== 1) {
            return '';
        }
        return $foldersWithName[0];
    }

    /**
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
     * @throws FolderDoesNotExistException
     */
    public function getFoldersInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $folderNameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ): array {
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($folderIdentifier);
        if ($scheme === CantoUtility::SCHEME_ALBUM) {
            // Albums contain only files, not folders.
            return [];
        }
        $folders = [];
        $explicitFolderIdentifier = CantoUtility::getIdFromCombinedIdentifier($folderIdentifier);
        $sortBy = GetTreeRequest::SORT_BY_NAME;
        $sortDirection = $sortRev ? GetTreeRequest::SORT_DIRECTION_DESC : GetTreeRequest::SORT_DIRECTION_ASC;
        $folderTree = $this->cantoRepository->getFolderIdentifierTree($sortBy, $sortDirection);
        if ($folderIdentifier === $this->rootFolderIdentifier) {
            $folderTree = $folderTree[$this->rootFolderIdentifier] ?? $folderTree;
        } else {
            $folderInformation = $this->cantoRepository->getFolderDetails($scheme, $explicitFolderIdentifier);
            $idPathSegments = str_getcsv($folderInformation['idPath'], '/');
            $lastSegmentIndex = count($idPathSegments) - 1;
            array_walk(
                $idPathSegments,
                static function (string &$folderIdentifier, int $key, $scheme) use ($lastSegmentIndex, $folderInformation) {
                    if ($key === $lastSegmentIndex) {
                        $scheme = $folderInformation['scheme'];
                    }
                    $folderIdentifier = CantoUtility::buildCombinedIdentifier($scheme, $folderIdentifier);
                },
                CantoUtility::SCHEME_FOLDER
            );
            if (in_array($this->rootFolderIdentifier, $idPathSegments)) {
                $idPathSegments = array_slice($idPathSegments, array_search($this->rootFolderIdentifier, $idPathSegments) + 1);
            }
            $idPath = implode('/', $idPathSegments);
            try {
                $folderTree = ArrayUtility::getValueByPath($folderTree, $idPath);
            } catch (MissingArrayPathException $e) {
            }
        }
        if ($recursive) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($folderTree), \RecursiveIteratorIterator::SELF_FIRST);
            $folderTree = iterator_to_array($iterator, true);
        }

        // $c is the counter for how many items we still have to fetch (-1 is unlimited)
        $c = $numberOfItems > 0 ? $numberOfItems : -1;
        foreach (array_keys($folderTree) as $identifier) {
            if ($c === 0) {
                break;
            }
            if ($start > 0) {
                $start--;
            } else {
                $folders[$identifier] = $identifier;
                --$c;
            }
        }
        return $folders;
    }

    /**
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     */
    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = []): int
    {
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($folderIdentifier);
        $explicitFolderIdentifier = CantoUtility::getIdFromCombinedIdentifier($folderIdentifier);
        if ($scheme === CantoUtility::SCHEME_FOLDER || $folderIdentifier === $this->rootFolderIdentifier) {
            // Folders can not have files, just other folders and albums.
            return 0;
        }
        return $this->cantoRepository->countFilesInFolder($explicitFolderIdentifier);
    }

    /**
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks callbacks for filtering the items
     * @throws FolderDoesNotExistException
     */
    public function countFoldersInFolder(
        $folderIdentifier,
        $recursive = false,
        array $folderNameFilterCallbacks = []
    ): int {
        return count($this->getFoldersInFolder(
            $folderIdentifier,
            0,
            0,
            $recursive,
            $folderNameFilterCallbacks
        ));
    }

    /**
     * @param string $identifier
     */
    public function hashIdentifier($identifier): string
    {
        $scheme = CantoUtility::getSchemeFromCombinedIdentifier($identifier);
        if (CantoUtility::isFolder($scheme)) {
            $identifier = $this->canonicalizeAndCheckFolderIdentifier($identifier);
        }
        $identifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
        return $this->hash($identifier, 'sha1');
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

    protected function buildRootFolderIdentifier(): string
    {
        $rootFolderScheme = CantoUtility::SCHEME_FOLDER;
        if (!empty($this->configuration['rootFolderScheme'])
            && $this->configuration['rootFolderScheme'] === CantoUtility::SCHEME_ALBUM
        ) {
            $rootFolderScheme = CantoUtility::SCHEME_ALBUM;
        }
        $rootFolder = self::ROOT_FOLDER;
        if (!empty($this->configuration['rootFolder'])) {
            $rootFolder = $this->configuration['rootFolder'];
        }

        return CantoUtility::buildCombinedIdentifier(
            $rootFolderScheme,
            $rootFolder
        );
    }

    /**
     * @param string $filePath The file path (most times filePath)
     */
    protected function canonicalizeAndCheckFilePath($filePath): string
    {
        return $filePath;
    }

    /**
     * @param string $fileIdentifier The file Identifier
     */
    protected function canonicalizeAndCheckFileIdentifier($fileIdentifier): string
    {
        return $fileIdentifier;
    }

    /**
     * @param string $folderIdentifier The folder identifier
     */
    protected function canonicalizeAndCheckFolderIdentifier($folderIdentifier): string
    {
        return $folderIdentifier;
    }

    /**
     * Transient File-Cache cleanup
     * @see https://review.typo3.org/#/c/36446/
     */
    public function __destruct()
    {
        foreach (self::$transientCachedFiles as $cachedFile) {
            if (file_exists($cachedFile)) {
                unlink($cachedFile);
            }
        }
    }

    public function streamFile(string $identifier, array $properties): ResponseInterface
    {
        $fileInfo = $this->getFileInfoByIdentifier($identifier, ['name', 'mimetype', 'mtime', 'size']);
        $downloadName = $properties['filename_overwrite'] ?? $fileInfo['name'] ?? '';
        $mimeType = $properties['mimetype_overwrite'] ?? $fileInfo['mimetype'] ?? '';
        $contentDisposition = ($properties['as_download'] ?? false) ? 'attachment' : 'inline';

        return new Response(
            new FalDumpFileContentsDecoratorStream($identifier, $this, (int)$fileInfo['size']),
            200,
            [
                'Content-Disposition' => $contentDisposition . '; filename="' . $downloadName . '"',
                'Content-Type' => $mimeType,
                'Content-Length' => (string)$fileInfo['size'],
                'Last-Modified' => gmdate('D, d M Y H:i:s', $fileInfo['mtime']) . ' GMT',
                // Cache-Control header is needed here to solve an issue with browser IE8 and lower
                // See for more information: http://support.microsoft.com/kb/323308
                'Cache-Control' => '',
            ]
        );
    }

    /**
     * Creates a folder, within a parent folder.
     * If no parent folder is given, a root level folder will be created
     *
     * @param string $newFolderName
     * @param string $parentFolderIdentifier
     * @param bool $recursive
     * @return string the Identifier of the new folder
     */
    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false): string
    {
        $createAlbum = str_starts_with($newFolderName, 'A:');
        $newFolderName = str_replace(['A:', 'F:'], '', $newFolderName);
        $request = new CreateAlbumFolderRequest($newFolderName);
        $request->setParentFolder(CantoUtility::getIdFromCombinedIdentifier($parentFolderIdentifier));
        try {
            if ($createAlbum) {
                return 'album<>' . $this->cantoRepository->getClient()->libraryTree()->createAlbum($request)->getId();
            }
            $folder = $this->cantoRepository->getClient()->libraryTree()->createFolder($request);
            $id = 'folder<>' . $folder->getId();
            $this->cantoRepository->setFolderCache($id, $folder->getResponseData());
            return $id;
        } catch (NotAuthorizedException|InvalidResponseException $e) {
            throw new RuntimeException('Creating the folder did not work - ' . $e->getMessage());
        }
    }

    /**
     * Renames a folder in this storage.
     *
     * @param string $folderIdentifier
     * @param string $newName
     * @return array A map of old to new file identifiers of all affected resources
     */
    public function renameFolder($folderIdentifier, $newName)
    {
        throw new NotSupportedException('Renaming a folder is currently not supported.', 1626963089);
    }

    /**
     * Removes a folder in filesystem.
     *
     * @param string $folderIdentifier
     * @param bool $deleteRecursively
     * @return bool
     */
    public function deleteFolder($folderIdentifier, $deleteRecursively = false)
    {
        $request = new DeleteFolderOrAlbumRequest();
        ['scheme' => $scheme, 'identifier' => $identifier] = CantoUtility::splitCombinedIdentifier($folderIdentifier);
        $request->addFolder($identifier, $scheme);
        try {
            return $this->cantoRepository->getClient()->libraryTree()->deleteFolderOrAlbum($request)->isSuccessful();
        } catch (\Exception $e) {
            if ($e->getPrevious() instanceof GuzzleException) {
                // replace with logger
                debug([$request, $e->getPrevious()->getMessage()]);
            }
        }
        CantoUtility::flushCache($this->cantoRepository);
        return false;
    }

    /**
     * Adds a file from the local server hard disk to a given path in TYPO3s
     * virtual file system. This assumes that the local file exists, so no
     * further check is done here! After a successful operation the original
     * file must not exist anymore.
     *
     * @param string $localFilePath within public web path
     * @param string $targetFolderIdentifier
     * @param string $newFileName optional, if not given original name is used
     * @param bool $removeOriginal if set the original file will be removed
     *                                after successful operation
     * @return string the identifier of the new file
     */
    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true)
    {
        $uploadSettingsRequest = new GetUploadSettingRequest(false);
        $response = $this->cantoRepository->getClient()->upload()->getUploadSetting($uploadSettingsRequest);
        $request = new UploadFileRequest(
            $localFilePath,
            $response,
        );
        $request->setScheme('image');
        if (CantoUtility::getSchemeFromCombinedIdentifier($targetFolderIdentifier) === 'folder') {
            throw new \Exception('Files need to be within an album, not a folder');
        }
        $request->setAlbumId(CantoUtility::getIdFromCombinedIdentifier($targetFolderIdentifier));
        $request->setFileName($newFileName);
        try {
            $this->cantoRepository->getClient()->upload()->uploadFile($request);
        } catch (NotAuthorizedException $e) {
            $this->sendFlashMessageToUser('Not Authorized', $e->getMessage(), FlashMessage::ERROR);
            throw $e;
        } catch (InvalidResponseException $e) {
            $this->sendFlashMessageToUser('Invalid Response', $e->getMessage(), FlashMessage::ERROR);
            throw $e;
        } catch (\JsonException $e) {
            $this->sendFlashMessageToUser('JSON Exception', $e->getMessage(), FlashMessage::ERROR);
            throw $e;
        }
        $id = '';
        $count = 0;
        while ($id === '') {
            $status = $this->cantoRepository->getClient()->upload()->queryUploadStatus(new QueryUploadStatusRequest());
            // We need to wait for AWS to process the image, only then will we be able to show, that the file has been uploaded successfully and display it in the list.
            // The file though has already been uploaded at this point, it just is not yet present in canto
            sleep(2);
            foreach ($status->getStatusItems() as $item) {
                if ($item->name === $newFileName && $item->status === Status::STATUS_DONE) {
                    $id = CantoUtility::buildCombinedIdentifier($item->scheme, $item->id);
                }
            }
            if (++$count > 15) {
                $this->sendFlashMessageToUser('Timeout', 'File not fully processed. Please reload', FlashMessage::WARNING, );
                return '';
            }
        }
        if ($id && $removeOriginal) {
            unlink($localFilePath);
        }
        CantoUtility::flushCache($this->cantoRepository);
        return $id;
    }

    /**
     * Creates a new (empty) file and returns the identifier.
     *
     * @param string $fileName
     * @param string $parentFolderIdentifier
     * @return string
     */
    public function createFile($fileName, $parentFolderIdentifier)
    {
        $path = '/tmp/' . $fileName;
        touch($path);
        $identifier = $this->addFile($path, $parentFolderIdentifier, $fileName);
        CantoUtility::flushCache($this->cantoRepository);
        return $identifier;
    }

    /**
     * Copies a file *within* the current storage.
     * Note that this is only about an inner storage copy action,
     * where a file is just copied to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $fileName
     * @return string the Identifier of the new file
     */
    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName)
    {
        throw new NotSupportedException('This driver does not support this operation yet.', 1626963232);
    }

    /**
     * Renames a file in this storage.
     *
     * @param string $fileIdentifier
     * @param string $newName The target path (including the file name!)
     * @return string The identifier of the file after renaming
     */
    public function renameFile($fileIdentifier, $newName)
    {
        ['scheme' => $scheme, 'identifier' => $identifier] = CantoUtility::splitCombinedIdentifier($fileIdentifier);
        $request = new RenameContentRequest($scheme, $identifier, $newName);
        try {
            $this->cantoRepository->getClient()->asset()->renameContent($request);
            CantoUtility::flushCache($this->cantoRepository);
        } catch (InvalidResponseException $e) {
            // replace with logger
            debug([$request, $e->getPrevious()->getMessage()]);
        }
        return $fileIdentifier;
    }

    /**
     * Replaces a file with file in local file system.
     *
     * @param string $fileIdentifier
     * @param string $localFilePath
     * @return bool TRUE if the operation succeeded
     */
    public function replaceFile($fileIdentifier, $localFilePath)
    {
        throw new NotSupportedException('This driver does not support this operation yet.', 1626963248);
    }

    /**
     * Removes a file from the filesystem. This does not check if the file is
     * still used or if it is a bad idea to delete it for some other reason
     * this has to be taken care of in the upper layers (e.g. the Storage)!
     *
     * @param string $fileIdentifier
     * @return bool TRUE if deleting the file succeeded
     */
    public function deleteFile($fileIdentifier)
    {
        ['scheme' => $scheme, 'identifier' => $identifier] = CantoUtility::splitCombinedIdentifier($fileIdentifier);
        $request = new BatchDeleteContentRequest();
        $request->addContent($scheme, $identifier);
        try {
            $this->cantoRepository->getClient()->asset()->batchDeleteContent($request);
        } catch (InvalidResponseException $e) {
            // replace with logger
            debug([$request, $e->getPrevious()->getMessage()]);
        }
        CantoUtility::flushCache($this->cantoRepository);
        return true;
    }

    /**
     * Moves a file *within* the current storage.
     * Note that this is only about an inner-storage move action,
     * where a file is just moved to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFileName
     * @return string
     */
    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName)
    {
        throw new NotSupportedException('This driver does not support this operation yet.', 1626963285);
    }

    /**
     * Folder equivalent to moveFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return array All files which are affected, map of old => new file identifiers
     */
    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        throw new NotSupportedException('This driver does not support this operation yet.', 1626963299);
    }

    /**
     * Folder equivalent to copyFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return bool
     */
    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        throw new NotSupportedException('This driver does not support this operation yet.', 1626963313);
    }

    /**
     * Sets the contents of a file to the specified value.
     *
     * @param string $fileIdentifier
     * @param string $contents
     * @return int The number of bytes written to the file
     */
    public function setFileContents($fileIdentifier, $contents)
    {
        throw new NotSupportedException('This driver does not support this operation yet.', 1626963332);
    }

    private function sendFlashMessageToUser(string $messageHeader, string $messageText, int $messageSeverity): void
    {
        $message = GeneralUtility::makeInstance(
            FlashMessage::class,
            $messageText,
            $messageHeader,
            $messageSeverity,
            true
        );
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $messageQueue->addMessage($message);
    }
}

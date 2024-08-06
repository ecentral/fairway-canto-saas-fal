# Canto SaaS FAL

## Asset Picker

### Configuration

The Canto asset picker can be globally enabled/disabled for each site
in the site configuration.

In addition, the asset picker must be enabled for editors
by setting `permissions.file.default.cantoAssetPicker = 1` in user TSconfig
or `permissions.file.storage.[storageUID].cantoAssetPicker = 1`.


### Update

1.0.7.3 - Fix ElementAssestBrowser for Typo3 11 and 10 and append a fallback class ElementAssetBrowserV11AndV10 and append maxitems for canto asset browser button
1.0.7.2 - Fixed the problem of missing height and width after attaching to a content element without calling the file tree in the side menu
1.0.7.1 - Fix wrong storageUid handling and warnings
1.0.7.0 - Implementation of a new file browser for the V12
1.0.6.73 - A check for the modified timestamp has been added. Only processed images modified in Canto are now deleted
1.0.6.7 - Fix wrong cli handling in combintaion with scheduler commands
1.0.6.6 - Fix wrong driver SyncMetaDataCategoriesEventListener Handling
1.0.6.5 - New handle category mapping in EventListener
1.0.6.4 - Append new Command job for clear the canto file cache and delete frontend processed images
1.0.6.3 - Change preview image size for typo3 backend
1.0.6.2 - Fix performace on backend and fronted filelisting
1.0.6.1 - Fix router handling of AfterFormEnginePageInitializedEventListener
1.0.6.0 - Update for TYPO3 v12
1.0.5.0 - Fix missing check of file source and append filedriver check

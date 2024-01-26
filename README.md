# Canto SaaS FAL

## Asset Picker

### Configuration

The Canto asset picker can be globally enabled/disabled for each site
in the site configuration.

In addition, the asset picker must be enabled for editors
by setting `permissions.file.default.cantoAssetPicker = 1` in user TSconfig
or `permissions.file.storage.[storageUID].cantoAssetPicker = 1`.


### Update

1.0.6.3 - Change preview image size for typo3 backend
1.0.6.2 - Fix performace on backend and fronted filelisting
1.0.6.1 - Fix router handling of AfterFormEnginePageInitializedEventListener
1.0.6.0 - Update for TYPO3 v12
1.0.5.0 - Fix missing check of file source and append filedriver check

# Canto SaaS FAL

## Asset Picker

### Configuration

The Canto asset picker can be globally enabled/disabled for each site
in the site configuration.

In addition, the asset picker must be enabled for editors
by setting `permissions.file.default.cantoAssetPicker = 1` in user TSconfig
or `permissions.file.storage.[storageUID].cantoAssetPicker = 1`.


### Update

1.0.1 - Fix missing check of file source and append filedriver check

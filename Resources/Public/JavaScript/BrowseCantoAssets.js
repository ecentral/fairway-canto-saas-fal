define([
  "TYPO3/CMS/Recordlist/ElementBrowser",
  "TYPO3/CMS/Core/Event/RegularEvent",
  "TYPO3/CMS/Core/Ajax/AjaxRequest",
  "TYPO3/CMS/Core/DocumentService",
  "./Storage/BrowserSession",
], function(ElementBrowser, RegularEvent, AjaxRequest, DocumentService, ClientStorage) {
  "use strict";

  var Selectors = {};
  (function(Selectors) {
    Selectors['close'] = '[data-close]';
    Selectors['searchForm'] = 'form.canto-asset-search-form';
    Selectors['resultContainer'] = 'div.canto-asset-search-result-container';
    Selectors['formFilterFields'] = '[data-canto-search]';
    Selectors['paginationPage'] = '[data-pagination-page]';
  })(Selectors);

  var StorageKeys = {};
  (function(StorageKeys) {
    StorageKeys['currentPage'] = 'cantoAssetPickerCurrentPage';
    StorageKeys['searchWord'] = 'cantoAssetPickerSearchWord';
    StorageKeys['type'] = 'cantoAssetPickerSearchType';
  })(StorageKeys);

  var defaultValues = {};
  (function(defaultValues) {
    defaultValues['currentPage'] = 1;
    defaultValues['searchWord'] = '';
    defaultValues['type'] = 'tags';
  })(defaultValues);

  class BrowseCantoAssets {
    constructor() {
      this.storageUid = document.body.dataset.storageUid;
      this.allowedFileExtensions = document.body.dataset.allowedFileExtensions;
      this.searchForm = document.querySelector(Selectors.searchForm);
      DocumentService.ready().then(() => {
        this.registerEvents();
        this.initializeResults();
      });
    }

    registerEvents() {
      const me = this;
      new RegularEvent('click', (event, targetEl) => {
        event.preventDefault();
        const promise = BrowseCantoAssets.importFile(targetEl.dataset.scheme, targetEl.dataset.identifier, me.storageUid);
        return promise.then((data) => {
          BrowseCantoAssets.insertElement(data.fileName, data.fileUid, true);
        });
      }).delegateTo(document, Selectors.close);

      new RegularEvent('submit', (event, targetEl) => {
        event.preventDefault();
        BrowseCantoAssets.doSearch(targetEl, me.storageUid, 1, me.allowedFileExtensions);
      }).delegateTo(document, Selectors.searchForm);

      new RegularEvent('click', (event, targetEl) => {
        event.preventDefault();
        const pageNumber = targetEl.dataset.paginationPage;
        BrowseCantoAssets.doSearch(me.searchForm, me.storageUid, pageNumber, me.allowedFileExtensions);
      }).delegateTo(document, Selectors.paginationPage);
    }

    initializeResults() {
      const pageNumber = ClientStorage.isset(StorageKeys.currentPage) ? ClientStorage.get(StorageKeys.currentPage) : defaultValues.currentPage;
      const searchWord = ClientStorage.isset(StorageKeys.searchWord) ? ClientStorage.get(StorageKeys.searchWord) : defaultValues.searchWord;
      const type = ClientStorage.isset(StorageKeys.type) ? ClientStorage.get(StorageKeys.type) : defaultValues.type;
      const filterFormFields = this.searchForm.querySelectorAll(Selectors.formFilterFields);
      for (let i = 0; i < filterFormFields.length; i++) {
        let key = filterFormFields[i].dataset.cantoSearch;
        switch (key) {
          case 'query':
            filterFormFields[i].value = searchWord;
            break;
          case 'type':
            filterFormFields[i].checked = filterFormFields[i].value === type;
            break;
        }
      }
      BrowseCantoAssets.doSearch(this.searchForm, this.storageUid, pageNumber, this.allowedFileExtensions);
    }

    static doSearch(filterForm, storageUid, pageNumber, allowedFileExtensions) {
      const resultContainer = document.querySelector(Selectors.resultContainer);
      BrowseCantoAssets.submitSearch(filterForm, storageUid, pageNumber, allowedFileExtensions).then((data) => {
        resultContainer.innerHTML = data;
      });
    }

    static submitSearch(filterForm, storageUid, pageNumber, allowedFileExtensions) {
      const queryParams = {
        page: pageNumber,
        storageUid: storageUid,
        search: BrowseCantoAssets.collectFilterData(filterForm),
        allowedFileExtensions: allowedFileExtensions
      };
      ClientStorage.set(StorageKeys.currentPage, queryParams['page'] ?? defaultValues.currentPage);
      ClientStorage.set(StorageKeys.searchWord, queryParams['search']['query'] ?? defaultValues.searchWord);
      ClientStorage.set(StorageKeys.type, queryParams['search']['type'] ?? defaultValues.type);
      return (new AjaxRequest(TYPO3.settings.ajaxUrls.search_canto_file))
        .withQueryArguments(queryParams)
        .get()
        .then(async (response) => {
          return await response.resolve();
        });
    }

    static collectFilterData(filterForm) {
      const searchParams = {};
      const filterFormFields = filterForm.querySelectorAll(Selectors.formFilterFields);
      for (let i = 0; i < filterFormFields.length; i++) {
        let key = filterFormFields[i].dataset.cantoSearch;
        switch (key) {
          case 'query':
            searchParams[key] = filterFormFields[i].value;
            break;
          case 'type':
            if (filterFormFields[i].checked === true) {
              searchParams[key] = filterFormFields[i].value;
            }
            break;
        }
      }
      return searchParams;
    }

    static importFile(scheme, identifier, storageUid) {
      const params = {
        'scheme': scheme,
        'identifier': identifier,
        'storageUid': storageUid
      };
      return (new AjaxRequest(TYPO3.settings.ajaxUrls.import_canto_file))
        .withQueryArguments(params)
        .get()
        .then(async (response) => {
          return await response.resolve();
        });
    }

    static insertElement(fileName, fileUid, close) {
      return ElementBrowser.insertElement('sys_file', String(fileUid), fileName, String(fileUid), close);
    }

  }

  return new BrowseCantoAssets;
});

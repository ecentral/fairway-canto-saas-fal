define([
  "TYPO3/CMS/Recordlist/ElementBrowser",
  "TYPO3/CMS/Core/Event/RegularEvent",
  "TYPO3/CMS/Core/Ajax/AjaxRequest",
  "TYPO3/CMS/Core/DocumentService",
  "./Storage/BrowserSession",
], function(ElementBrowser, RegularEvent, AjaxRequest, DocumentService, ClientStorage) {
  "use strict";

  var Selectors;
  (function(Selectors) {
    Selectors['close'] = '[data-close]';
    Selectors['searchForm'] = 'form.canto-asset-search-form';
    Selectors['resultContainer'] = 'div.canto-asset-search-result-container';
    Selectors['formFilterFields'] = '[data-canto-search]';
    Selectors['paginationPage'] = '[data-pagination-page]';
  })(Selectors || (Selectors = {}));

  var StorageKeys;
  (function(StorageKeys) {
    StorageKeys['currentPage'] = 'cantoAssetPickerCurrentPage';
    StorageKeys['searchWord'] = 'cantoAssetPickerSearchWord';
    StorageKeys['searchInField'] = 'cantoAssetPickerSearchInField';
  })(StorageKeys || (StorageKeys = {}));

  var defaultValues;
  (function(defaultValues) {
    defaultValues['currentPage'] = 1;
    defaultValues['searchWord'] = '';
    defaultValues['searchInField'] = 'all';
  })(defaultValues || (defaultValues = {}));

  class BrowseCantoAssets {
    constructor(storageUid) {
      this.storageUid = storageUid;
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
        BrowseCantoAssets.doSearch(targetEl, me.storageUid, 1);
      }).delegateTo(document, Selectors.searchForm);

      new RegularEvent('click', (event, targetEl) => {
        event.preventDefault();
        const pageNumber = targetEl.dataset.paginationPage;
        BrowseCantoAssets.doSearch(me.searchForm, me.storageUid, pageNumber);
      }).delegateTo(document, Selectors.paginationPage);
    }

    initializeResults() {
      const pageNumber = ClientStorage.isset(StorageKeys.currentPage) ? ClientStorage.get(StorageKeys.currentPage) : defaultValues.currentPage;
      const searchWord = ClientStorage.isset(StorageKeys.searchWord) ? ClientStorage.get(StorageKeys.searchWord) : defaultValues.searchWord;
      const searchInField = ClientStorage.isset(StorageKeys.searchInField) ? ClientStorage.get(StorageKeys.searchInField) : defaultValues.searchInField;
      const filterFormFields = this.searchForm.querySelectorAll(Selectors.formFilterFields);
      for (let i = 0; i < filterFormFields.length; i++) {
        let key = filterFormFields[i].dataset.cantoSearch;
        switch (key) {
          case 'query':
            filterFormFields[i].value = searchWord;
            break;
          case 'searchInField':
            filterFormFields[i].checked = filterFormFields[i].value === searchInField;
            break;
        }
      }
      BrowseCantoAssets.doSearch(this.searchForm, this.storageUid, pageNumber);
    }

    static doSearch(filterForm, storageUid, pageNumber) {
      const resultContainer = document.querySelector(Selectors.resultContainer);
      BrowseCantoAssets.submitSearch(filterForm, storageUid, pageNumber).then((data) => {
        resultContainer.innerHTML = data;
      });
    }

    static submitSearch(filterForm, storageUid, pageNumber) {
      const queryParams = {
        page: pageNumber,
        storageUid: storageUid,
        search: BrowseCantoAssets.collectFilterData(filterForm)
      };
      ClientStorage.set(StorageKeys.currentPage, queryParams['page'] ?? defaultValues.currentPage);
      ClientStorage.set(StorageKeys.searchWord, queryParams['search']['query'] ?? defaultValues.searchWord);
      ClientStorage.set(StorageKeys.searchInField, queryParams['search']['searchInField'] ?? defaultValues.searchInField);
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
          case 'searchInField':
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

  return BrowseCantoAssets;
});

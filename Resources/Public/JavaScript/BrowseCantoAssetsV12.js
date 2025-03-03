import ElementBrowser from "@typo3/backend/element-browser.js";
import RegularEvent from "@typo3/core/event/regular-event.js";
import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import DocumentService from "@typo3/core/document-service.js";
import ClientStorage from "@typo3/backend/storage/browser-session.js";

const Selectors = {
  close: '[data-close]',
  importFile: 'button.canto-picker-import-file',
  searchForm: 'form.canto-asset-search-form',
  resultContainer: 'div.canto-asset-search-result-container',
  formFilterFields: '[data-canto-search]',
  paginationPage: '[data-pagination-page]',
  searchFormType: '[data-canto-search="type"]',
};

const StorageKeys = {
  currentPage: 'cantoAssetPickerCurrentPage',
  searchWord: 'cantoAssetPickerSearchWord',
  type: 'cantoAssetPickerSearchType',
  identifier: 'cantoAssetPickerSearchIdentifier',
  scheme: 'cantoAssetPickerSearchScheme',
};

const defaultValues = {
  currentPage: 1,
  searchWord: '',
  type: 'tags',
  identifier: '',
  scheme: 'image',
};

class BrowseCantoAssets {
  constructor() {
    this.storageUid = document.body.dataset.storageUid;
    this.allowedFileExtensions = document.body.dataset.allowedFileExtensions;
    this.searchForm = document.querySelector(Selectors.searchForm);
    DocumentService.ready().then(() => {
      this.registerEvents();
      this.initializeResults();
      BrowseCantoAssets.updateForm(this.searchForm);
    });
  }

  isBetweenNumbers(number, a, b) {
    if (Number.isNaN(number) || Number.isNaN(a) || Number.isNaN(b)) {
      return false;
    }
    const min = Math.min(a, b);
    const max = Math.max(a, b);
    return number > min && number < max;
  }

  registerEvents() {
    new RegularEvent('click', (event, targetEl) => {
      event.preventDefault();
      /** @type {HTMLElement|null} importFileButton */
      const importFileButton = targetEl.querySelector(Selectors.importFile);

      if (importFileButton) {
        const positionImportFileButton = importFileButton.getBoundingClientRect();
        if (
          this.isBetweenNumbers(event.clientX, positionImportFileButton.left, positionImportFileButton.right) &&
          this.isBetweenNumbers(event.clientY, positionImportFileButton.top, positionImportFileButton.bottom)
        ) {
          return BrowseCantoAssets.importFile(targetEl.dataset.scheme, targetEl.dataset.identifier, this.storageUid)
            .then((data) => {
              return BrowseCantoAssets.insertElement(data.fileName, data.fileUid, true);
            });
        }
      }
    }).delegateTo(document, Selectors.close);

    new RegularEvent('submit', (event, targetEl) => {
      event.preventDefault();
      BrowseCantoAssets.doSearch(targetEl, this.storageUid, 1, this.allowedFileExtensions);
    }).delegateTo(document, Selectors.searchForm);

    new RegularEvent('click', (event, targetEl) => {
      event.preventDefault();
      const pageNumber = targetEl.dataset.paginationPage;
      BrowseCantoAssets.doSearch(this.searchForm, this.storageUid, pageNumber, this.allowedFileExtensions);
    }).delegateTo(document, Selectors.paginationPage);

    new RegularEvent('change', () => {
      BrowseCantoAssets.updateForm(this.searchForm);
    }).delegateTo(this.searchForm, Selectors.searchFormType);
  }

  initializeResults() {
    const pageNumber = ClientStorage.isset(StorageKeys.currentPage) ? ClientStorage.get(StorageKeys.currentPage) : defaultValues.currentPage;
    const searchWord = ClientStorage.isset(StorageKeys.searchWord) ? ClientStorage.get(StorageKeys.searchWord) : defaultValues.searchWord;
    const type = ClientStorage.isset(StorageKeys.type) ? ClientStorage.get(StorageKeys.type) : defaultValues.type;
    const identifier = ClientStorage.isset(StorageKeys.identifier) ? ClientStorage.get(StorageKeys.identifier) : defaultValues.identifier;
    const scheme = ClientStorage.isset(StorageKeys.scheme) ? ClientStorage.get(StorageKeys.scheme) : defaultValues.scheme;
    const filterFormFields = this.searchForm.querySelectorAll(Selectors.formFilterFields);
    for (let i = 0; i < filterFormFields.length; i++) {
      let key = filterFormFields[i].dataset.cantoSearch;
      switch (key) {
        case 'query':
          filterFormFields[i].value = searchWord;
          break;
        case 'identifier':
          filterFormFields[i].value = identifier;
          break;
        case 'scheme':
          //filterFormFields[i].querySelector('[value="' + scheme + '"]').sel
          filterFormFields[i].value = scheme;
          break;
        case 'type':
          filterFormFields[i].checked = filterFormFields[i].value === type;
          break;
      }
    }
    BrowseCantoAssets.doSearch(this.searchForm, this.storageUid, pageNumber, this.allowedFileExtensions);
  }

  static updateForm(filterForm) {
    const type = filterForm.querySelector(Selectors.searchFormType + ':checked').value;
    [].forEach.call(filterForm.querySelectorAll('[data-canto-show-by-types]'), (container) => {
      if (container.dataset.cantoShowByTypes.split(',').indexOf(type) !== -1) {
        container.classList.remove('hidden');
      } else {
        container.classList.add('hidden');
      }
    });
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
    ClientStorage.set(StorageKeys.identifier, queryParams['search']['identifier'] ?? defaultValues.identifier);
    ClientStorage.set(StorageKeys.scheme, queryParams['search']['scheme'] ?? defaultValues.scheme);
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
      if (filterFormFields[i].offsetParent === null) {
        continue;
      }
      let key = filterFormFields[i].dataset.cantoSearch;
      switch (key) {
        case 'query':
        case 'identifier':
        case 'scheme':
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

  /**
   * @async
   * @param scheme
   * @param identifier
   * @param storageUid
   * @returns {Promise<{fileName: string, fileUid: number}>}
   */
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

  /**
   * @async
   * @param scheme
   * @param identifier
   * @param storageUid
   * @returns {Promise<{fileName: string, fileUid: number}>}
   */
  static addCdnFile(scheme, identifier, storageUid) {
    const params = {
      'scheme': scheme,
      'identifier': identifier,
      'storageUid': storageUid
    };
    return (new AjaxRequest(TYPO3.settings.ajaxUrls.add_canto_cdn_file))
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

export default new BrowseCantoAssets;

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
  folderToggle: '.folder-toggle',
  folderLabel: '.folder-label',
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

    window.CantoTree = {
      selectFolder: (parentId, folderId, element) => this.selectFolder(parentId, folderId, element),
      toggleFolder: (element) => this.toggleFolder(element)
    };

    DocumentService.ready().then(() => {
      this.registerEvents();
      this.initializeResults();

      if (this.searchForm) {
        BrowseCantoAssets.updateForm(this.searchForm);
      }

      this.initCantoTree();
    });
  }

  initCantoTree() {
    const urlParams = new URLSearchParams(window.location.search);
    const albumId = urlParams.get('albumId');

    if (albumId) {
      const albumElement = document.querySelector(`${Selectors.folderLabel}[data-id="${albumId}"]`);
      if (albumElement) {
        albumElement.classList.add('selected');

        let parentSubtree = albumElement.closest('.subtree');
        while (parentSubtree) {
          parentSubtree.style.display = 'block';
          const toggleElement = parentSubtree.previousElementSibling.querySelector(Selectors.folderToggle);
          if (toggleElement) {
            toggleElement.classList.add('open');
          }
          parentSubtree = parentSubtree.parentElement.closest('.subtree');
        }
      }

      this.processAlbumContents();
    }

    this.registerCantoTreeEvents();
  }

  selectFolder(parentId, folderId, element) {
    document.querySelectorAll(`${Selectors.folderLabel}.selected`).forEach(el => {
      el.classList.remove('selected');
    });

    if (element) {
      element.classList.add('selected');
    }

    const parts = folderId.split('<>');
    const scheme = parts[0];
    const id = parts[1];

    if (scheme === 'album') {
      window.location.href = window.location.pathname +
        '?albumId=' + id +
        '&' + window.location.search.substring(1).replace(/&?albumId=[^&]*/, '');
    } else {
      const folderToggle = element.parentElement.querySelector(Selectors.folderToggle);
      if (folderToggle) {
        this.toggleFolder(folderToggle);
      }
    }
  }

  toggleFolder(element) {
    const folderItem = element.closest('.canto-folder-item');
    const subtree = folderItem?.nextElementSibling;

    if (subtree && subtree.classList.contains('subtree')) {
      if (subtree.style.display === 'none' || !subtree.style.display) {
        subtree.style.display = 'block';
        element.classList.add('open');
      } else {
        subtree.style.display = 'none';
        element.classList.remove('open');
      }
    }
  }


  processAlbumContents() {
    const PaginationSettings = {
      itemsPerPage: 12,
      maxPagesToShow: 5
    };

    const albumItems = document.querySelectorAll('.canto-asset-grid .canto-asset-item');
    const resultContainer = document.querySelector(Selectors.resultContainer);

    if (albumItems.length > 0 && resultContainer) {
      const urlParams = new URLSearchParams(window.location.search);
      const currentPage = parseInt(urlParams.get('page')) || 1;

      const itemsPerPage = parseInt(localStorage.getItem('cantoItemsPerPage')) || PaginationSettings.itemsPerPage;

      const totalItems = albumItems.length;
      const totalPages = Math.ceil(totalItems / itemsPerPage);
      const startIndex = (currentPage - 1) * itemsPerPage;
      const endIndex = Math.min(startIndex + itemsPerPage, totalItems);

      const visibleItems = Array.from(albumItems).slice(startIndex, endIndex);

      let html = `
    <div class="row canto-result">
      <div class="col-xs-12 d-flex justify-content-between align-items-center">
        <p class="canto-result-count">
          ${totalItems} results found
        </p>
        <div class="form-inline">
          <label for="itemsPerPage">Items per page: </label>
          <select id="itemsPerPage" class="form-control ml-2">
            <option value="12" ${itemsPerPage === 12 ? 'selected' : ''}>12</option>
            <option value="24" ${itemsPerPage === 24 ? 'selected' : ''}>24</option>
            <option value="48" ${itemsPerPage === 48 ? 'selected' : ''}>48</option>
            <option value="96" ${itemsPerPage === 96 ? 'selected' : ''}>96</option>
          </select>
        </div>
      </div>
    `;

      visibleItems.forEach(item => {
        const assetId = item.dataset.assetId;
        const assetType = item.dataset.scheme || 'image';
        const assetName = item.querySelector('.asset-name')?.textContent || '';
        const previewImg = item.querySelector('img.asset-preview');
        const previewUrl = previewImg ? previewImg.getAttribute('src') : '';

        html += `
      <a href="#" title="Add file" class="col-canto-search-result-item" data-scheme="${assetType}" data-identifier="${assetId}" data-close="1">
        ${previewUrl ?
          `<img src="${previewUrl}" alt="${assetName}">` :
          `<div class="asset-type-icon ${assetType}">${assetType}</div>`}
        <div class="canto-result-item-info">
          <h5>${assetName}</h5>
          <button class="btn canto-action-button canto-picker-import-file">Cloud</button>
        </div>
      </a>
      `;
      });

      html += `</div>`;

      if (totalPages > 1) {
        html += this.createPaginationHTML(currentPage, totalPages);
      }

      resultContainer.innerHTML = html;

      const itemsPerPageSelect = document.getElementById('itemsPerPage');
      if (itemsPerPageSelect) {
        itemsPerPageSelect.addEventListener('change', (event) => {
          localStorage.setItem('cantoItemsPerPage', event.target.value);

          const newUrl = new URL(window.location.href);
          newUrl.searchParams.set('page', '1');
          window.location.href = newUrl.toString();
        });
      }

      const albumGrid = document.querySelector('.canto-asset-grid');
      if (albumGrid) {
        albumGrid.style.display = 'none';
      }
    }
  }

  createPaginationHTML(currentPage, totalPages) {
    const maxPagesToShow = PaginationSettings.maxPagesToShow;
    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

    if (endPage - startPage + 1 < maxPagesToShow) {
      startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }

    let paginationHTML = `
  <nav aria-label="Search result navigation" class="text-center">
    <ul class="pagination pagination-lg">
      <li class="${currentPage === 1 ? 'disabled' : ''}">
        <a href="#" aria-label="Previous" data-pagination-page="${currentPage > 1 ? currentPage - 1 : ''}">
          <span aria-hidden="true">«</span>
        </a>
      </li>
  `;

    if (startPage > 1) {
      paginationHTML += `
      <li>
        <a href="#" data-pagination-page="1">1</a>
      </li>
    `;

      if (startPage > 2) {
        paginationHTML += `
        <li class="disabled">
          <span>...</span>
        </li>
      `;
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      paginationHTML += `
      <li class="${i === currentPage ? 'active' : ''}">
        <a href="#" data-pagination-page="${i}">${i}</a>
      </li>
    `;
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        paginationHTML += `
        <li class="disabled">
          <span>...</span>
        </li>
      `;
      }

      paginationHTML += `
      <li>
        <a href="#" data-pagination-page="${totalPages}">${totalPages}</a>
      </li>
    `;
    }

    paginationHTML += `
      <li class="${currentPage === totalPages ? 'disabled' : ''}">
        <a href="#" aria-label="Next" data-pagination-page="${currentPage < totalPages ? currentPage + 1 : ''}">
          <span aria-hidden="true">»</span>
        </a>
      </li>
    </ul>
  </nav>
  `;

    return paginationHTML;
  }

  registerCantoTreeEvents() {
    new RegularEvent('click', (event, target) => {
      this.toggleFolder(target);
    }).delegateTo(document, Selectors.folderToggle);
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

    if (this.searchForm) {
      new RegularEvent('submit', (event, targetEl) => {
        event.preventDefault();
        BrowseCantoAssets.doSearch(targetEl, this.storageUid, 1, this.allowedFileExtensions);
      }).delegateTo(document, Selectors.searchForm);

      new RegularEvent('change', () => {
        BrowseCantoAssets.updateForm(this.searchForm);
      }).delegateTo(this.searchForm, Selectors.searchFormType);
    }

    new RegularEvent('click', (event, targetEl) => {
      event.preventDefault();
      const pageNumber = targetEl.dataset.paginationPage;
      BrowseCantoAssets.doSearch(this.searchForm, this.storageUid, pageNumber, this.allowedFileExtensions);
    }).delegateTo(document, Selectors.paginationPage);

    new RegularEvent('click', (event, targetEl) => {
      event.preventDefault();
      const pageNumber = targetEl.dataset.paginationPage;
      if (pageNumber) {
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('page', pageNumber);
        window.location.href = newUrl.toString();
      }
    }).delegateTo(document, Selectors.paginationPage);
  }

  initializeResults() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('albumId') || !this.searchForm) {
      return;
    }

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
          filterFormFields[i].value = scheme;
          break;
        case 'type':
          filterFormFields[i].checked = filterFormFields[i].value === type;
          break;
      }
    }
    //BrowseCantoAssets.doSearch(this.searchForm, this.storageUid, pageNumber, this.allowedFileExtensions);
  }

  static updateForm(filterForm) {
    if (!filterForm) return;

    const checkedRadio = filterForm.querySelector(Selectors.searchFormType + ':checked');
    if (!checkedRadio) return;

    const type = checkedRadio.value;
    [].forEach.call(filterForm.querySelectorAll('[data-canto-show-by-types]'), (container) => {
      if (container.dataset.cantoShowByTypes.split(',').indexOf(type) !== -1) {
        container.classList.remove('hidden');
      } else {
        container.classList.add('hidden');
      }
    });
  }

  static doSearch(filterForm, storageUid, pageNumber, allowedFileExtensions) {
    if (!filterForm) return Promise.reject(new Error('Suchformular nicht gefunden'));

    const resultContainer = document.querySelector(Selectors.resultContainer);
    if (!resultContainer) return Promise.reject(new Error('Ergebniscontainer nicht gefunden'));

    return BrowseCantoAssets.submitSearch(filterForm, storageUid, pageNumber, allowedFileExtensions).then((data) => {
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

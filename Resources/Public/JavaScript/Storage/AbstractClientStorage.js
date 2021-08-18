/**
 * This class is for TYPO3 v10 compatibility.
 * TYPO3 v11 ships this class by default, see TYPO3/CMS/Backend/Storage/AbstractClientStorage.
 */
define([
  "require",
  "exports"
], function (require, exports) {
  "use strict";
  Object.defineProperty(exports, "__esModule", { value: true });
  class AbstractClientStorage {
    constructor() {
      this.keyPrefix = 't3-';
      this.storage = null;
    }
    get(key) {
      if (this.storage === null) {
        return null;
      }
      return this.storage.getItem(this.keyPrefix + key);
    }
    set(key, value) {
      if (this.storage !== null) {
        this.storage.setItem(this.keyPrefix + key, value);
      }
    }
    unset(key) {
      if (this.storage !== null) {
        this.storage.removeItem(this.keyPrefix + key);
      }
    }
    unsetByPrefix(prefix) {
      if (this.storage === null) {
        return;
      }
      prefix = this.keyPrefix + prefix;
      Object.keys(this.storage)
        .filter((key) => key.startsWith(prefix))
        .forEach((key) => this.storage.removeItem(key));
    }
    clear() {
      if (this.storage !== null) {
        this.storage.clear();
      }
    }
    isset(key) {
      if (this.storage === null) {
        return false;
      }
      return this.get(key) !== null;
    }
  }
  exports.default = AbstractClientStorage;
});

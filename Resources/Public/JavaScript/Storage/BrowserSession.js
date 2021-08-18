/**
 * This class is for TYPO3 v10 compatibility.
 * TYPO3 v11 ships this class by default, see TYPO3/CMS/Backend/Storage/BrowserSession.
 */
var __importDefault = (this && this.__importDefault) || function (mod) {
  return (mod && mod.__esModule) ? mod : { "default": mod };
};
define([
  "require",
  "exports",
  "./AbstractClientStorage"
], function (require, exports, AbstractClientStorage) {
  "use strict";
  AbstractClientStorage = __importDefault(AbstractClientStorage);
  class BrowserSession extends AbstractClientStorage.default {
    constructor() {
      super();
      this.storage = sessionStorage;
    }
  }
  return new BrowserSession();
});

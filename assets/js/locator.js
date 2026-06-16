/**
 * Locator — client-side store search/filter.
 *
 * Progressive enhancement: every store is rendered server-side, so the directory
 * works with JavaScript disabled. When JS runs, typing in the search box filters
 * the visible cards by their data-locator-haystack attribute. No network calls.
 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  function setupLocator(root) {
    var input = root.querySelector('[data-locator-search]');
    var items = Array.prototype.slice.call(root.querySelectorAll('[data-locator-item]'));
    var count = root.querySelector('[data-locator-count]');
    var noResults = root.querySelector('[data-locator-noresults]');

    if (!input || items.length === 0) {
      return;
    }

    var total = items.length;
    var countTemplate = count ? count.textContent : '';

    function updateCount(visible) {
      if (!count) {
        return;
      }
      // Replace the leading number in the original "%d location(s)" string.
      if (visible === total) {
        count.textContent = countTemplate;
      } else {
        count.textContent = String(visible) + ' / ' + countTemplate;
      }
    }

    function filter() {
      var query = input.value.trim().toLowerCase();
      var visible = 0;

      items.forEach(function (item) {
        var haystack = item.getAttribute('data-locator-haystack') || '';
        var match = query === '' || haystack.indexOf(query) !== -1;
        item.hidden = !match;
        if (match) {
          visible += 1;
        }
      });

      if (noResults) {
        noResults.hidden = visible !== 0;
      }

      updateCount(visible);
    }

    function replant() {
      // Re-arm the pin-drop / ping animation by restarting the CSS class.
      root.classList.remove('is-narrowing');
      // Force reflow so the next add re-triggers the keyframes.
      void root.offsetWidth;
      root.classList.add('is-narrowing');
    }

    var debounce;
    input.addEventListener('input', function () {
      window.clearTimeout(debounce);
      debounce = window.setTimeout(function () {
        filter();
        replant();
      }, 120);
    });

    // Run once in case the field is pre-filled (e.g. browser restore).
    if (input.value.trim() !== '') {
      filter();
    }
  }

  ready(function () {
    Array.prototype.slice
      .call(document.querySelectorAll('[data-locator]'))
      .forEach(setupLocator);
  });
})();

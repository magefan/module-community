/**
 * Minimal polyfills for browsers from 2015+
 */
(function() {
    // Element.closest polyfill (for IE11)
    if (!Element.prototype.closest) {
        Element.prototype.closest = function(s) {
            var el = this;
            do {
                if (el.matches(s)) return el;
                el = el.parentElement || el.parentNode;
            } while (el !== null && el.nodeType === 1);
            return null;
        };
    }

    // Element.matches polyfill (for IE11)
    if (!Element.prototype.matches) {
        Element.prototype.matches = Element.prototype.msMatchesSelector ||
            Element.prototype.webkitMatchesSelector;
    }

    // Array.from polyfill (for IE11)
    if (!Array.from) {
        Array.from = function(arrayLike) {
            return Array.prototype.slice.call(arrayLike);
        };
    }
})();

/**
 * Magefan Menu Manager - Compatible with browsers from 2015+
 */
var MagefanMenuManager = {
    // Configuration
    config: {
        menuSelector: '#menu-magefan-community-elements',
        defaultBgColor: '#4a4542',
        searchThreshold: 16,
        menuOffsetHeight: 147,
        menuLeftPosition: '88px'
    },

    // Cache DOM elements
    elements: {},

    /**
     * Initialize the menu manager
     */
    init: function() {
        this.elements.menu = document.querySelector(this.config.menuSelector);

        if (!this.elements.menu) {
            return;
        }

        this.cacheElements();
        this.setupMenu();
    },

    /**
     * Cache frequently used DOM elements
     */
    cacheElements: function() {
        this.elements.bgSubmenu = document.querySelector('.admin__menu .level-0 > .submenu');
        this.elements.level1Parents = this.elements.menu.querySelectorAll('.level-1.parent');
        this.elements.submenuContainer = this.elements.menu.querySelector('.level-0 > .submenu > ul[role="menu"]');
        this.elements.closeButton = this.elements.menu.querySelector('.action-close');
    },

    /**
     * Get background color for submenu
     */
    getSubmenuBgColor: function() {
        if (this.elements.bgSubmenu) {
            return window.getComputedStyle(this.elements.bgSubmenu).backgroundColor;
        }
        return this.config.defaultBgColor;
    },

    /**
     * Setup the entire menu
     */
    setupMenu: function() {
        var bgColor = this.getSubmenuBgColor();

        this.processLevel1Parents(bgColor);
        this.reorganizeSubmenu();
        this.setupMenuObserver();
    },

    /**
     * Process all level-1 parent items
     */
    processLevel1Parents: function(bgColor) {
        var self = this;

        Array.from(this.elements.level1Parents).forEach(function(parent) {
            var submenu = parent.querySelector('.submenu');
            var groupTitleSpan = parent.querySelector('.submenu-group-title span');
            var groupTitleParent = groupTitleSpan.parentElement;
            if (groupTitleParent) {
                var link = document.createElement('a');
                link.href = "#";
                link.className = groupTitleParent.className;
                link.innerHTML = groupTitleParent.innerHTML;

                groupTitleParent.replaceWith(link);
            }

            if (!groupTitleSpan || !submenu) {
                return;
            }

            // Set background color
            submenu.style.backgroundColor = bgColor;

            // Skip if already processed
            if (submenu.querySelector('.submenu-item-title')) {
                return;
            }

            var titleText = groupTitleSpan.textContent.trim();

            self.addSubmenuHeader(submenu, titleText);
            self.attachSubmenuEvents(parent, submenu);
        });
    },

    /**
     * Add header elements to submenu
     */
    addSubmenuHeader: function(submenu, titleText) {
        // Create title
        var itemTitle = document.createElement('strong');
        itemTitle.className = 'submenu-item-title';
        itemTitle.textContent = titleText;

        // Create close button
        var closeBtn = document.createElement('a');
        closeBtn.href = '#';
        closeBtn.className = 'action-close-submenu';

        // Prepend elements
        submenu.insertBefore(closeBtn, submenu.firstChild);
        submenu.insertBefore(itemTitle, submenu.firstChild);

        return closeBtn;
    },

    /**
     * Attach event listeners to submenu
     */
    attachSubmenuEvents: function(parent, submenu) {
        var self = this;
        var closeBtn = submenu.querySelector('.action-close-submenu');

        // Close button click
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();

                setTimeout(function() {
                    self.closeActiveSubmenus();
                    self.closeActiveLevel1menus();
                }, 50);
            });
        }

        // Parent click to open submenu
        parent.addEventListener('click', function() {
            self.toggleSubmenu(parent);
        });
    },

    /**
     * Toggle submenu visibility
     */
    toggleSubmenu: function(parent) {
        var wasOpened = parent.classList.contains('active');

        this.closeActiveLevel1menus();
        this.closeActiveSubmenus();

        if (wasOpened) {
            return;
        }

        parent.classList.add('active');

        var submenu = parent.querySelector('.submenu');
        if (submenu) {
            submenu.classList.add('_show');
        }
    },

    /**
     * Reorganize submenu structure
     */
    reorganizeSubmenu: function() {
        if (!this.elements.submenuContainer) {
            return;
        }

        var allLevel1Parents = Array.from(
            this.elements.menu.querySelectorAll('.level-0 > .submenu .parent.level-1')
        );

        // Clear container and re-add items
        this.elements.submenuContainer.innerHTML = '';
        allLevel1Parents.forEach(function(parent) {
            this.elements.submenuContainer.appendChild(parent);
        }, this);

        // Sort items alphabetically
        this.sortSubmenuItems();

        // Add search if threshold met
        if (allLevel1Parents.length > this.config.searchThreshold) {
            this.addSearchFunctionality(allLevel1Parents);
        }
    },

    /**
     * Sort submenu items alphabetically
     */
    sortSubmenuItems: function() {
        var items = Array.from(this.elements.submenuContainer.querySelectorAll('.parent.level-1'));

        items.sort(function(a, b) {
            var spanA = a.querySelector('.submenu-group-title span');
            var spanB = b.querySelector('.submenu-group-title span');
            var textA = (spanA ? spanA.textContent : '').trim();
            var textB = (spanB ? spanB.textContent : '').trim();
            return textA.localeCompare(textB);
        });

        items.forEach(function(item) {
            this.elements.submenuContainer.appendChild(item);
        }, this);
    },

    /**
     * Add search functionality
     */
    addSearchFunctionality: function(items) {
        var searchContainer = this.createSearchContainer();
        this.elements.submenuContainer.parentElement.insertBefore(
            searchContainer,
            this.elements.submenuContainer
        );

        var searchInput = searchContainer.querySelector('.mf-menu-search-input');
        var searchClose = searchContainer.querySelector('.action-search-close');

        this.attachSearchEvents(searchInput, searchClose, items);
    },

    /**
     * Create search container with input and close button
     */
    createSearchContainer: function() {
        var container = document.createElement('div');
        container.className = 'mf-menu-search-container';

        var input = document.createElement('input');
        input.type = 'text';
        input.placeholder = 'Search in the menu...';
        input.className = 'mf-menu-search-input';

        var closeBtn = document.createElement('div');
        closeBtn.className = 'action-search-close';

        container.appendChild(input);
        container.appendChild(closeBtn);

        return container;
    },

    /**
     * Attach search event listeners
     */
    attachSearchEvents: function(searchInput, searchClose, items) {
        var self = this;

        searchInput.addEventListener('input', function() {
            self.handleSearch(searchInput, searchClose, items);
        });

        searchClose.addEventListener('click', function() {
            self.clearSearch(searchInput, searchClose, items);
        });
    },

    /**
     * Handle search input
     */
    handleSearch: function(searchInput, searchClose, items) {
        var searchText = searchInput.value.toLowerCase().trim();

        // Toggle close button visibility
        searchClose.classList.toggle('_show', searchText.length > 0);

        items.forEach(function(item) {
            var titleSpan = item.querySelector('.submenu-group-title span');
            if (!titleSpan) return;

            var titleText = titleSpan.textContent.toLowerCase();
            var isMatch = searchText === '' || titleText.indexOf(searchText) !== -1;

            // Show/hide item
            item.style.display = isMatch ? '' : 'none';

            // Highlight matching text
            this.highlightText(titleSpan, searchText, isMatch);
        }, this);
    },

    /**
     * Highlight matching text
     */
    highlightText: function(titleSpan, searchText, isMatch) {
        var originalText = titleSpan.getAttribute('data-original-text') || titleSpan.textContent;

        // Store original text on first search
        if (!titleSpan.getAttribute('data-original-text')) {
            titleSpan.setAttribute('data-original-text', originalText);
        }

        if (searchText !== '' && isMatch) {
            var regex = new RegExp('(' + this.escapeRegex(searchText) + ')', 'gi');
            titleSpan.innerHTML = originalText.replace(
                regex,
                '<mark style="background: #eb5202;">$1</mark>'
            );
        } else {
            titleSpan.textContent = originalText;
        }
    },

    /**
     * Escape special regex characters
     */
    escapeRegex: function(text) {
        return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    },

    /**
     * Clear search and reset items
     */
    clearSearch: function(searchInput, searchClose, items) {
        searchInput.value = '';
        searchClose.classList.remove('_show');

        items.forEach(function(item) {
            item.style.display = '';

            var titleSpan = item.querySelector('.submenu-group-title span');
            if (titleSpan) {
                var originalText = titleSpan.getAttribute('data-original-text');
                if (originalText) {
                    titleSpan.textContent = originalText;
                }
            }
        });

        searchInput.focus();
    },

    /**
     * Setup mutation observer for menu state changes
     */
    setupMenuObserver: function() {
        var self = this;
        var observer = new MutationObserver(function() {
            self.handleMenuStateChange();
        });

        observer.observe(this.elements.menu, {
            attributes: true,
            attributeFilter: ['class']
        });
    },

    /**
     * Handle menu state changes
     */
    handleMenuStateChange: function() {
        var isMenuOpen = this.elements.menu.classList.contains('_show');
        var submenuDiv = this.elements.menu.querySelector('.submenu');
        var submenuList = this.elements.menu.querySelector('.submenu > ul[role="menu"]');

        if (!isMenuOpen) {
            this.closeActiveSubmenus();
            this.closeActiveLevel1menus();
            document.body.style.overflowY = 'auto';
            return;
        }

        if (submenuDiv && submenuList) {
            this.adjustSubmenuPosition(submenuDiv, submenuList);
            document.body.style.overflowY = 'hidden';
        }
    },

    /**
     * Close all active level1 menus
     */
    closeActiveLevel1menus: function() {
        Array.from(this.elements.level1Parents).forEach(function(parent) {
            parent.classList.remove('active');
        });
    },

    /**
     * Close all active submenus
     */
    closeActiveSubmenus: function() {
        var activeSubmenu = this.elements.menu.querySelector('.level-1.parent.active .submenu._show');
        if (activeSubmenu) {
            activeSubmenu.classList.remove('_show');
        }
    },

    /**
     * Adjust submenu position and height
     */
    adjustSubmenuPosition: function(submenuDiv, submenuList) {
        var searchContainer = this.elements.menu.querySelector('.mf-menu-search-container');
        var searchHeight = searchContainer ? searchContainer.offsetHeight : 0;

        var maxHeight = document.documentElement.clientHeight - this.config.menuOffsetHeight - searchHeight;

        submenuList.style.maxHeight = maxHeight + 'px';
        submenuDiv.style.position = 'fixed';
        submenuDiv.style.left = this.config.menuLeftPosition;
    }
};

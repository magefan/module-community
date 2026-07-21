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
        menuLeftPosition: '88px',
        groups: []
    },

    // Cache DOM elements
    elements: {},

    /**
     * Initialize the menu manager
     *
     * @param {Array} groups
     */
    init: function(groups) {
        this.config.groups = groups || [];
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

        // Reorganize first so group items exist before processing
        this.reorganizeSubmenu();

        // After reorganization, level1Parents = only direct-child items (groups + ungrouped)
        this.elements.level1Parents = this.elements.submenuContainer
            ? Array.from(this.elements.submenuContainer.children).filter(function(el) {
                return el.classList.contains('level-1') && el.classList.contains('parent');
            })
            : [];

        this.processLevel1Parents(bgColor);
        this.processLevel2Parents(bgColor);
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

            if (!groupTitleSpan) return;

            var groupTitleParent = groupTitleSpan.parentElement;
            if (groupTitleParent && groupTitleParent.nodeName === 'STRONG') {
                var link = document.createElement('a');
                link.href = "#";
                 link.className = 'mf-submenu-group-title';
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
     * Process level-2 module items inside group submenus (level-3 panels)
     */
    processLevel2Parents: function(bgColor) {
        var self = this;

        var level2Items = Array.from(
            this.elements.menu.querySelectorAll('.level-1.parent[data-mf-level2]')
        );

        level2Items.forEach(function(parent) {
            // Get the direct child .submenu (module options panel)
            var submenu = null;
            var children = parent.children;
            for (var i = 0; i < children.length; i++) {
                if (children[i].classList.contains('submenu')) {
                    submenu = children[i];
                    break;
                }
            }
            if (!submenu) return;

            submenu.style.backgroundColor = bgColor;

            // Skip if already processed
            if (submenu.querySelector('.submenu-item-title')) return;

            // Convert <strong class="submenu-group-title"> to <a class="mf-submenu-group-title">
            var strongTitle = parent.querySelector('strong.submenu-group-title');
            if (strongTitle) {
                var link = document.createElement('a');
                link.href = '#';
                link.className = 'mf-submenu-group-title';
                link.innerHTML = strongTitle.innerHTML;
                strongTitle.replaceWith(link);
            }

            var titleEl = parent.querySelector('.mf-submenu-group-title span') ||
                          parent.querySelector('.submenu-group-title span');
            var titleText = titleEl ? titleEl.textContent.trim() : '';

            self.addSubmenuHeader(submenu, titleText);

            // Close button for level-3 panel
            var closeBtn = submenu.querySelector('.action-close-submenu');
            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    setTimeout(function() {
                        self.closeActiveLevel2Submenus();
                    }, 50);
                });
            }

            // Click on module item opens level-3 panel; stop propagation to parent group
            parent.addEventListener('click', function(e) {
                e.stopPropagation();
                self.toggleLevel2Submenu(parent);
            });
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
     * Toggle module options panel (level-3)
     */
    toggleLevel2Submenu: function(parent) {
        var wasOpened = parent.classList.contains('active');

        this.closeActiveLevel2Submenus();

        if (wasOpened) {
            return;
        }

        parent.classList.add('active');

        var submenu = null;
        var children = parent.children;
        for (var i = 0; i < children.length; i++) {
            if (children[i].classList.contains('submenu')) {
                submenu = children[i];
                break;
            }
        }
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

        // Sort and optionally group items
        this.sortAndGroupSubmenuItems();

        // Add search if threshold met.
        // Collect top-level items AFTER grouping so group wrappers (SEO, Page Speed Optimization)
        // are included and can be properly hidden/shown during search.
        if (allLevel1Parents.length > this.config.searchThreshold) {
            var topLevelItems = Array.from(this.elements.submenuContainer.children).filter(function(el) {
                return el.classList.contains('parent') && el.classList.contains('level-1');
            });
            this.addSearchFunctionality(topLevelItems);
        }
    },

    /**
     * Sort submenu items alphabetically and group them if groups are configured
     */
    sortAndGroupSubmenuItems: function() {
        var self = this;
        var LAST_ITEMS = [
            'menu-magefan-community-magefan-extensions',
            'menu-magefan-community-magefan-user-guides',
        ];

        var items = Array.from(this.elements.submenuContainer.querySelectorAll('.parent.level-1'));

        var lastItems = items.filter(function(item) {
            return LAST_ITEMS.includes(item.getAttribute('data-ui-id'));
        });

        var sortableItems = items.filter(function(item) {
            return !LAST_ITEMS.includes(item.getAttribute('data-ui-id'));
        });

        var getItemText = function(item) {
            var span = item.querySelector('.mf-submenu-group-title span') ||
                       item.querySelector('.submenu-group-title span');
            return (span ? span.textContent : '').trim();
        };

        var groups = this.config.groups || [];

        if (!groups.length) {
            sortableItems.sort(function(a, b) {
                return getItemText(a).localeCompare(getItemText(b));
            });
            sortableItems.forEach(function(item) {
                self.elements.submenuContainer.appendChild(item);
            });
            lastItems.forEach(function(item) {
                self.elements.submenuContainer.appendChild(item);
            });
            return;
        }

        // Pull items into groups; remaining go ungrouped
        var remaining = sortableItems.slice();
        var groupSections = [];

        groups.forEach(function(group) {
            var groupItems = [];
            var baseItem = null;

            group.modules.forEach(function(prefix) {
                for (var i = 0; i < remaining.length; i++) {
                    var uiId = remaining[i].getAttribute('data-ui-id') || '';
                    if (uiId.indexOf(prefix) === 0) {
                        var item = remaining.splice(i, 1)[0];
                        if (group.base && uiId.indexOf(group.base) === 0) {
                            baseItem = item;
                        } else {
                            groupItems.push(item);
                        }
                        break;
                    }
                }
            });

            if (!baseItem && !groupItems.length) {
                return;
            }

            groupItems.sort(function(a, b) {
                return getItemText(a).localeCompare(getItemText(b));
            });

            if (baseItem) {
                var titleEl = baseItem.querySelector('.submenu-group-title span') ||
                              baseItem.querySelector('.mf-submenu-group-title span');
                if (titleEl) {
                    titleEl.textContent = 'General';
                    titleEl.setAttribute('data-original-text', 'General');
                }
                groupItems.unshift(baseItem);
            }

            groupSections.push({name: group.name, items: groupItems});
        });

        // Build group wrapper elements (do not append yet)
        var allSortable = [];

        groupSections.forEach(function(section) {
            if (!section.items.length) return;

            // Create group wrapper .level-1.parent
            var groupLi = document.createElement('li');
            groupLi.className = 'level-1 parent';
            groupLi.setAttribute('data-mf-group', '1');

            // Group title — same structure processLevel1Parents expects
            var groupTitle = document.createElement('strong');
            groupTitle.className = 'submenu-group-title';
            var groupTitleSpan = document.createElement('span');
            groupTitleSpan.textContent = section.name;
            groupTitle.appendChild(groupTitleSpan);
            groupLi.appendChild(groupTitle);

            // Group submenu panel
            var groupSubmenu = document.createElement('div');
            groupSubmenu.className = 'submenu';

            var groupUl = document.createElement('ul');
            groupUl.setAttribute('role', 'menu');

            // Move module items inside group panel and mark them as level-2
            section.items.forEach(function(item) {
                item.setAttribute('data-mf-level2', '1');
                groupUl.appendChild(item);
            });

            groupSubmenu.appendChild(groupUl);
            groupLi.appendChild(groupSubmenu);

            allSortable.push({ name: section.name, element: groupLi });
        });

        // Add ungrouped items to the same sortable list
        remaining.forEach(function(item) {
            allSortable.push({ name: getItemText(item), element: item });
        });

        // Sort all items (groups and ungrouped) together A-Z
        allSortable.sort(function(a, b) {
            return a.name.localeCompare(b.name);
        });

        // Rebuild: all items sorted A-Z, then pinned last items
        allSortable.forEach(function(entry) {
            self.elements.submenuContainer.appendChild(entry.element);
        });

        lastItems.forEach(function(item) {
            self.elements.submenuContainer.appendChild(item);
        });
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
            var titleSpan = item.querySelector('.mf-submenu-group-title span');
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

            var titleSpan = item.querySelector('.mf-submenu-group-title span');
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
     * Close all active level-2 (module options) submenus
     */
    closeActiveLevel2Submenus: function() {
        var activeL2 = Array.from(
            this.elements.menu.querySelectorAll('.level-1.parent[data-mf-level2].active')
        );
        activeL2.forEach(function(parent) {
            parent.classList.remove('active');
            var children = parent.children;
            for (var i = 0; i < children.length; i++) {
                if (children[i].classList.contains('submenu') &&
                    children[i].classList.contains('_show')) {
                    children[i].classList.remove('_show');
                    break;
                }
            }
        });
    },

    /**
     * Close all active submenus
     */
    closeActiveSubmenus: function() {
        this.closeActiveLevel2Submenus();
        var activeSubmenu = this.elements.menu.querySelector('.level-1.parent.active > .submenu._show');
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

(function() {
    function addMainUserGuideAndMarketplaceLinks() {
        /**
         * Magefan Menu Extensions and User Guides
         */
        const menuItems = [
            {
                selector: '[data-ui-id="menu-magefan-community-magefan-extensions"]',
                url: 'https://magefan.com/magento-2-extensions?utm_source=admin&utm_medium=menu&utm_campaign=extensions',
            },
            {
                selector: '[data-ui-id="menu-magefan-community-magefan-user-guides"]',
                url: 'https://magefan.com/blog/user-guides?utm_source=admin&utm_medium=menu&utm_campaign=guides',
            },
        ];

        menuItems.forEach(function(item) {
            let el = document.querySelector(item.selector);
            if (!el) return;

            // replace <strong> with <a>
            let strong = el.querySelector('strong.submenu-group-title');
            if (strong) {
                let a = document.createElement('a');
                a.href      = item.url;
                a.target = '_blank';
                a.innerHTML = strong.innerHTML;
                a.className = 'mf-submenu-group-title';

                strong.parentNode.replaceChild(a, strong);
            }

            // hide dummy submenu
            let submenu = el.querySelector('.submenu');
            if (submenu) submenu.style.display = 'none';

        });
    }

    function addUserGuideLinks() {
        document.querySelectorAll('a[href*="mf-ug-url-start"]').forEach(function(link) {
            var match =  link.getAttribute('href')
                .match(/mf-ug-url-start(.+?)mf-ug-url-end/);
            if (!match) return;

            var encoded = match[1];
            var decoded;

            try {
                // Convert base64url back to standard base64 before decoding
                var b64 = encoded.replace(/-/g, '+').replace(/_/g, '/');
                while (b64.length % 4) { b64 += '='; }
                decoded = atob(b64);
            } catch (e) {
                return;
            }

            if (!decoded) return;

            link.href = decoded + '?utm_source=admin&utm_medium=menu&utm_campaign=sub-guide';
            link.target = '_blank';
            link.addEventListener('click', function(e) {
                e.preventDefault();
                window.open(link.href, '_blank');
            });
        });
    }

    addMainUserGuideAndMarketplaceLinks();
    addUserGuideLinks();
})();

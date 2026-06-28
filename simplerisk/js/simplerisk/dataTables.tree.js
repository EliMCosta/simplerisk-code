/******************************************************************
****************SimpleRisk Tree adapter for DataTables*************
*******************************************************************
* Replaces EasyUI's treegrid: collapsible, arbitrarily-deep tree
* tables rendered on top of DataTables (which has no native tree).
*
* It consumes the EXISTING server payloads unchanged, i.e. the nested
* EasyUI-format data:
*     { totalCount, rows: [ {...fields, children: [...]} ] }
* or the json_response() envelope:
*     { status, status_message, data: <same nested shape or array> }
*
* The nested tree is flattened client-side into a flat row array, each
* row stamped with _sr = { id, parentId, depth, hasChildren, expanded }.
* Expand/collapse is driven purely by row visibility recomputed in
* drawCallback (we never remove/add rows, so DataTables' cache stays
* consistent). Drag-to-reparent uses native HTML5 drag-and-drop on a
* grip handle; status changes are handled by the page via buttons.
*******************************************************************/
(function ($) {
    'use strict';

    if (!$ || !$.fn || !$.fn.DataTable) {
        return;
    }

    // Count every node in a nested tree (for totalCount when omitted by server)
    function countNodes(nested) {
        if (!nested) return 0;
        var n = 0;
        for (var i = 0; i < nested.length; i++) {
            n++;
            if (nested[i] && nested[i].children) {
                n += countNodes(nested[i].children);
            }
        }
        return n;
    }

    // Recursively flatten a nested tree into a flat array, stamping _sr metadata.
    // parentId is the id of the immediate enclosing node (null/0 for roots).
    function flattenTree(nested, parentId, depth, out, idField, lazyLoad) {
        if (!nested) return out;
        for (var i = 0; i < nested.length; i++) {
            var row = nested[i];
            if (!row) continue;
            var kids = row.children ? row.children : null;
            var id = row[idField];
            var hasLazyChildren = !!(lazyLoad && row.hasLazyChildren);
            // Shallow-copy so we don't mutate the server payload
            var flat = $.extend({}, row, {
                _sr: {
                    id: id,
                    parentId: parentId,
                    depth: depth,
                    hasChildren: !!(kids && kids.length) || hasLazyChildren,
                    lazyChildren: hasLazyChildren
                }
            });
            out.push(flat);
            if (kids && kids.length) {
                flattenTree(kids, id, depth + 1, out, idField, lazyLoad);
            }
        }
        return out;
    }

    // Find a node in a nested tree by idField and attach lazy-loaded children.
    function mergeLazyChildren(nested, idField, parentId, children) {
        if (!nested) return false;
        for (var i = 0; i < nested.length; i++) {
            var row = nested[i];
            if (!row) continue;
            if (row[idField] == parentId) {
                row.children = children;
                row.hasLazyChildren = false;
                return true;
            }
            if (row.children && mergeLazyChildren(row.children, idField, parentId, children)) {
                return true;
            }
        }
        return false;
    }

    // Pure visibility computation. Given a flat list of {id, parentId} in
    // depth-first pre-order and an isOpen(id) predicate, returns a map id->bool.
    // A row is visible iff every ancestor is itself visible AND expanded. Because
    // the flat list is pre-order, each parent is visited before its children.
    function computeVisibility(flat, isOpenFn) {
        var visible = {};
        for (var i = 0; i < flat.length; i++) {
            var id = flat[i].id;
            var pid = flat[i].parentId;
            var isRoot = (pid === null || pid === undefined || pid === 0 || pid === '');
            visible[id] = isRoot ? true : (visible[pid] === true && !!isOpenFn(pid));
        }
        return visible;
    }

    /***************************************************************
     * Column renderer for the tree control cell (toggle + indent). *
     * Reads row._sr.{depth, hasChildren, expanded}.                *
     ***************************************************************/
    DataTable.render.srTreeControl = function (opts) {
        opts = opts || {};
        var indent = opts.indent == null ? 1.5 : opts.indent; // rem per depth level
        var draggable = !!opts.draggable;
        var moveLabel = opts.moveLabel || 'Move';
        return function (data, type, row) {
            if (type === 'display') {
                var sr = (row && row._sr) ? row._sr : {};
                var depth = sr.depth || 0;
                var hasChildren = !!sr.hasChildren;
                var isOpen = sr.expanded !== false; // default expanded
                var pad = 'padding-left:' + (depth * indent) + 'rem;';
                var html = '<div class="sr-tree-cell d-inline-flex align-items-center gap-1" style="' + pad + '">';
                if (hasChildren) {
                    html += '<span class="sr-tree-toggle cursor-pointer" data-sr-toggle="' + sr.id + '" role="button" tabindex="0">'
                        + '<i class="fa ' + (isOpen ? 'fa-caret-down' : 'fa-caret-right') + '"></i></span>';
                } else {
                    html += '<span class="sr-tree-leaf"></span>';
                }
                if (draggable) {
                    html += '<span class="sr-tree-grip cursor-grab" title="' + moveLabel + '" data-sr-grip="' + sr.id + '" draggable="true">'
                        + '<i class="fa fa-grip-vertical"></i></span>';
                }
                html += '<span class="sr-tree-label">' + (data == null ? '' : data) + '</span>';
                html += '</div>';
                return html;
            }
            // Search/order/type use the raw value
            return data;
        };
    };

    /***************************************************************
     * jQuery plugin: $(table).simpleriskTree(options)              *
     ***************************************************************/
    $.fn.simpleriskTree = function (options) {
        var defaults = {
            idField: 'value',          // row identity field (value|id|...)
            treeField: 'name',         // column whose data === this gets the tree control
            expandAll: true,           // initial expanded state for parent nodes
            indent: 1.5,               // rem of left padding per depth level
            ordering: false,           // sorting a tree breaks the hierarchy
            searching: false,          // no global search box on tree tables by default
            columns: [],
            ajax: {},                  // { url, type, data }
            lazyLoad: false,           // true | { idParam: 'id' } — fetch children on first expand
            dnd: false,                // { reparent: { url, idParam, parentParam, type, reload } }
            onLoad: null,              // function (api, json) called after each ajax load
            onError: null,             // function (api, xhr) called after a failed ajax load
            // Paginate root rows server-side (e.g. asset groups). Children for the
            // current page are still eager-nested in the same response.
            serverPagination: null     // { pageSize, pageSizes, pageParam, rowsParam }
        };
        var opts = $.extend({}, defaults, options);

        return this.each(function () {
            var $table = $(this);
            if ($table.data('sr-tree-init')) {
                // Already initialized: just reload data
                try { $table.DataTable().ajax.reload(); } catch (e) {}
                return;
            }
            $table.data('sr-tree-init', true);

            // Per-table mutable state
            var state = {
                expanded: {},      // id -> bool (only meaningful for hasChildren rows)
                filterRules: [],   // [{field, op, value}] sent as ?filterRules=<json> on each ajax load
                nestedRoots: [],   // nested tree cache (used by lazyLoad)
                loadedChildren: {}, // parent id -> true once lazy children are merged
                pagination: opts.serverPagination ? {
                    page: 1,
                    rows: opts.serverPagination.pageSize || 10
                } : null
            };
            // onLoad must run after draw, not on xhr: row DOM is replaced during draw,
            // so direct .click() handlers bound from onLoad would attach to stale nodes.
            var pendingOnLoadJson = null;

            // Resolve the column definitions. If the caller supplied a columns
            // array, use it; otherwise auto-build from <th data-field="..."> in
            // the table header (used by tables whose columns are dynamic, e.g.
            // the customization-aware asset-group grid).
            var baseColumns = (opts.columns && opts.columns.length)
                ? opts.columns
                : (function () {
                    var cols = [];
                    $table.find('thead tr:first th').each(function () {
                        var $th = $(this);
                        var field = $th.attr('data-field');
                        if (!field) { cols.push({}); return; }
                        var col = { data: field, defaultContent: '' };
                        if ($th.attr('width')) { col.width = $th.attr('width'); }
                        if (field === 'actions') { col.orderable = false; col.searchable = false; col.className = 'text-center'; }
                        cols.push(col);
                    });
                    return cols;
                })();

            // defaultContent: '' on every data column — tree payloads are often
            // heterogeneous (parent header rows vs leaf detail rows). EasyUI
            // tolerated missing fields; DataTables throws TN/4 without a fallback.
            var columns = baseColumns.map(function (col) {
                var c = col.data ? $.extend({ defaultContent: '' }, col) : $.extend({}, col);
                if (c.data === opts.treeField) {
                    var prevRender = c.render;
                    var control = DataTable.render.srTreeControl({
                        indent: opts.indent,
                        draggable: !!(opts.dnd && opts.dnd.reparent),
                        moveLabel: (opts.lang && opts.lang.Move) || 'Move'
                    });
                    c.render = function (data, type, row) {
                        if (type === 'display') {
                            return control(data, type, row);
                        }
                        return (typeof prevRender === 'function') ? prevRender(data, type, row) : data;
                    };
                    c.className = (c.className ? c.className + ' ' : '') + 'sr-tree-col';
                }
                return c;
            });

            var lazyLoadEnabled = !!opts.lazyLoad;
            var lazyIdParam = (opts.lazyLoad && opts.lazyLoad.idParam) ? opts.lazyLoad.idParam : 'id';

            var rebuildFromNested = function () {
                var flat = flattenTree(state.nestedRoots, null, 0, [], opts.idField, lazyLoadEnabled);
                for (var i = 0; i < flat.length; i++) {
                    var sr = flat[i]._sr;
                    if (sr.hasChildren && state.expanded[sr.id] === undefined) {
                        state.expanded[sr.id] = !!opts.expandAll;
                    }
                }
                api.rows().remove();
                if (flat.length) {
                    api.rows.add(flat);
                }
                api.draw(false);
            };

            var buildAjaxParams = function (extra) {
                var d = {};
                if (typeof opts.ajax.data === 'function') {
                    var more = opts.ajax.data.call($table[0], d);
                    if (more && typeof more === 'object') {
                        $.extend(d, more);
                    }
                } else if (opts.ajax.data && typeof opts.ajax.data === 'object') {
                    $.extend(d, opts.ajax.data);
                }
                if (state.filterRules.length) {
                    d.filterRules = JSON.stringify(state.filterRules);
                }
                if (state.pagination) {
                    var sp = opts.serverPagination || {};
                    d[sp.pageParam || 'page'] = state.pagination.page;
                    d[sp.rowsParam || 'rows'] = state.pagination.rows;
                }
                if (extra && typeof extra === 'object') {
                    $.extend(d, extra);
                }
                return d;
            };

            var fetchLazyChildren = function (parentId, done) {
                var params = buildAjaxParams({});
                params[lazyIdParam] = parentId;
                $.ajax({
                    url: opts.ajax.url,
                    type: opts.ajax.type || 'GET',
                    data: params,
                    dataType: 'json',
                    success: function (json) {
                        var payload = json;
                        if (json && json.status !== undefined && json.data !== undefined) {
                            payload = json.data;
                        }
                        var children = Array.isArray(payload) ? payload : (payload && payload.rows ? payload.rows : []);
                        mergeLazyChildren(state.nestedRoots, opts.idField, parentId, children);
                        state.loadedChildren[parentId] = true;
                        state.expanded[parentId] = true;
                        rebuildFromNested();
                        if (typeof done === 'function') {
                            done();
                        }
                    },
                    error: function (xhr) {
                        state.expanded[parentId] = false;
                        if (typeof retryCSRF === 'function' && retryCSRF(xhr, this)) {
                            return;
                        }
                        if (xhr.responseJSON && xhr.responseJSON.status_message && typeof showAlertsFromArray === 'function') {
                            showAlertsFromArray(xhr.responseJSON.status_message);
                        }
                        api.draw(false);
                    }
                });
            };

            var isOpen = function (id) {
                return state.expanded[id] !== false;
            };

            // Recompute row visibility from the expanded state, then apply it to
            // the rendered <tr> nodes. Delegates the pure ancestor-chain math to
            // computeVisibility() (also unit-tested directly).
            var applyVisibility = function (api) {
                var flat = [];
                var nodes = [];
                api.rows().every(function () {
                    var d = this.data();
                    var sr = (d && d._sr) ? d._sr : {};
                    flat.push({ id: sr.id, parentId: sr.parentId });
                    nodes.push(this.node());
                });
                var visible = computeVisibility(flat, function (id) { return isOpen(id); });
                for (var i = 0; i < flat.length; i++) {
                    if (nodes[i]) {
                        nodes[i].style.display = visible[flat[i].id] ? '' : 'none';
                    }
                }
            };

            var api = $table.DataTable({
                columns: columns,
                ordering: opts.ordering,
                searching: opts.searching,
                paging: false,
                info: false,
                serverSide: false,
                processing: true,
                deferRender: true,
                orderCellsTop: true,
                autoWidth: true,
                language: (typeof _lang !== 'undefined' && _lang) ? {
                    processing: _lang['Loading'] || 'Loading...'
                } : undefined,
                ajax: {
                    url: opts.ajax.url,
                    type: opts.ajax.type || 'GET',
                    data: function (d) {
                        if (typeof opts.ajax.data === 'function') {
                            var extra = opts.ajax.data.call(this, d);
                            // Callers may mutate d or return a plain object (EasyUI-era
                            // treegrid pages often used the return-object style).
                            if (extra && typeof extra === 'object') {
                                $.extend(d, extra);
                            }
                        } else if (opts.ajax.data && typeof opts.ajax.data === 'object') {
                            $.extend(d, opts.ajax.data);
                        }
                        if (state.filterRules.length) {
                            d.filterRules = JSON.stringify(state.filterRules);
                        }
                        if (state.pagination) {
                            var sp = opts.serverPagination || {};
                            d[sp.pageParam || 'page'] = state.pagination.page;
                            d[sp.rowsParam || 'rows'] = state.pagination.rows;
                        }
                    },
                    dataSrc: function (json) {
                        // Unwrap the json_response() envelope if present
                        var payload = json;
                        if (json && json.status !== undefined && json.data !== undefined) {
                            payload = json.data;
                        }
                        var nested;
                        if (payload && payload.rows !== undefined) {
                            nested = payload.rows;
                        } else if (Array.isArray(payload)) {
                            nested = payload;
                        } else {
                            nested = [];
                        }
                        state.nestedRoots = nested;
                        state.loadedChildren = {};
                        var flat = flattenTree(nested, null, 0, [], opts.idField, lazyLoadEnabled);
                        // Seed initial expanded state for any new parent nodes
                        for (var i = 0; i < flat.length; i++) {
                            var sr = flat[i]._sr;
                            if (sr.hasChildren && state.expanded[sr.id] === undefined) {
                                state.expanded[sr.id] = !!opts.expandAll;
                            }
                        }
                        return flat;
                    },
                    error: function (xhr) {
                        if (typeof retryCSRF === 'function' && retryCSRF(xhr, this)) {
                            return;
                        }
                        // Clear the processing overlay when the load fails; otherwise
                        // the table spinner hangs forever (seen on asset-group/tree when
                        // required ?page=&rows= params were never sent).
                        try {
                            new $.fn.dataTable.Api(this).processing(false);
                        } catch (e) {}
                        if (xhr.responseJSON && xhr.responseJSON.status_message && typeof showAlertsFromArray === 'function') {
                            showAlertsFromArray(xhr.responseJSON.status_message);
                        } else if (xhr.responseJSON && xhr.responseJSON.message && typeof showAlertFromMessage === 'function') {
                            showAlertFromMessage(xhr.responseJSON.message, false);
                        }
                        if (typeof opts.onError === 'function') {
                            try {
                                opts.onError.call(new $.fn.dataTable.Api(this), new $.fn.dataTable.Api(this), xhr);
                            } catch (err) {}
                        }
                    }
                },
                // Sync the expanded state into each row's _sr so the control
                // renderer and visibility computation always read the same truth.
                preDrawCallback: function (settings) {
                    var a = new $.fn.dataTable.Api(settings);
                    a.rows().every(function () {
                        var d = this.data();
                        if (d && d._sr) {
                            d._sr.expanded = isOpen(d._sr.id);
                        }
                    });
                },
                createdRow: function (node, data) {
                    var sr = (data && data._sr) ? data._sr : {};
                    node.setAttribute('data-sr-id', (sr.id != null ? sr.id : ''));
                    node.setAttribute('data-sr-parent', (sr.parentId != null ? sr.parentId : ''));
                    node.classList.add('sr-tree-row');
                    if (sr.depth) node.classList.add('sr-tree-depth-' + sr.depth);
                    if (sr.hasChildren) node.classList.add('sr-tree-parent');
                    if (opts.dnd && opts.dnd.reparent) {
                        node.setAttribute('data-sr-droppable', '1');
                    }
                },
                drawCallback: function (settings) {
                    applyVisibility(new $.fn.dataTable.Api(settings));
                    if (pendingOnLoadJson !== null && typeof opts.onLoad === 'function') {
                        var json = pendingOnLoadJson;
                        pendingOnLoadJson = null;
                        if (json) {
                            opts.onLoad.call(api, api, json);
                        }
                    }
                },
                initComplete: function () {
                    if (typeof opts.onLoad === 'function') {
                        opts.onLoad.call(api, api, null);
                    }
                    wireFilters();
                    wireServerPagination();
                }
            });

            // Stage onLoad for the draw that follows each successful ajax response.
            api.on('xhr', function (e, settings, json) {
                pendingOnLoadJson = json;
                updateServerPagination(json);
                $table.find('thead select[data-sr-filter-url]').each(function () {
                    populateFilterSelect($(this));
                });
            });

            // --- Programmatic control (for pages that toggle/expand externally) ---
            api.srToggle = function (id) {
                if (id == null) return;
                var opening = !isOpen(id);
                if (opening && lazyLoadEnabled && !state.loadedChildren[id]) {
                    var needsLazy = false;
                    api.rows().every(function () {
                        var d = this.data();
                        if (d && d._sr && d._sr.id == id && d._sr.lazyChildren) {
                            needsLazy = true;
                        }
                    });
                    if (needsLazy) {
                        api.processing(true);
                        fetchLazyChildren(id, function () {
                            api.processing(false);
                        });
                        return;
                    }
                }
                state.expanded[id] = opening;
                api.draw(false);
            };
            api.srExpandAll = function (expand) {
                api.rows().every(function () {
                    var d = this.data();
                    if (d && d._sr && d._sr.hasChildren) {
                        state.expanded[d._sr.id] = !!expand;
                    }
                });
                api.draw(false);
            };

            // --- Column filtering ---
            // Any element in the table header carrying data-sr-filter="<field>"
            // (with optional data-sr-op) drives server-side filtering: its value is
            // collected into filterRules and the table reloaded. Supports <input>
            // (debounced) and <select> (on change). The server endpoints already
            // consume ?filterRules=<json> (EasyUI's remoteFilter contract).
            function populateFilterSelect($el) {
                if ($el.data('sr-filter-populated')) {
                    return;
                }
                $el.data('sr-filter-populated', true);
                var url = $el.attr('data-sr-filter-url');
                var def = $el.attr('data-sr-filter-default');
                if (def) {
                    $('<option>').attr('value', '').text(def).appendTo($el);
                }
                if (!url) {
                    return;
                }
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    success: function (res) {
                        var items = (res && res.data) ? res.data : (Array.isArray(res) ? res : []);
                        for (var i = 0; i < items.length; i++) {
                            var it = items[i];
                            $('<option>')
                                .attr('value', (it.value != null ? it.value : it.id))
                                .text(it.name || it.label || it.value)
                                .appendTo($el);
                        }
                    }
                });
            }

            function wireFilters() {
                if (!$table.find('thead [data-sr-filter]').length) {
                    return;
                }
                // Populate every filter row (original + DataTables scroll-head clone).
                $table.find('thead select[data-sr-filter-url]').each(function () {
                    populateFilterSelect($(this));
                });
                if ($table.data('sr-filters-wired')) {
                    return;
                }
                $table.data('sr-filters-wired', true);

                var filterTimers = {};
                $table.on('change', 'thead select[data-sr-filter]', function () {
                    var $el = $(this);
                    setFilterRule($el.attr('data-sr-filter'), $el.attr('data-sr-op') || 'equal', $el.val());
                });
                $table.on('keyup', 'thead input[data-sr-filter]', function () {
                    var $el = $(this);
                    var field = $el.attr('data-sr-filter');
                    var val = $el.val();
                    clearTimeout(filterTimers[field]);
                    filterTimers[field] = setTimeout(function () {
                        setFilterRule(field, $el.attr('data-sr-op') || 'contains', val);
                    }, 400);
                });
            }
            function setFilterRule(field, op, value) {
                state.filterRules = state.filterRules.filter(function (r) {
                    return r.field !== field;
                });
                if (value !== '' && value != null) {
                    state.filterRules.push({ field: field, op: op, value: value });
                }
                if (state.pagination) {
                    state.pagination.page = 1;
                }
                state.loadedChildren = {};
                api.ajax.reload();
            }

            // --- Server-side pagination (root rows only) ---
            var $paginationBar = null;

            function paginationTotal(json) {
                if (!json) return 0;
                var sp = opts.serverPagination || {};
                if (typeof sp.totalFromJson === 'function') {
                    return sp.totalFromJson(json);
                }
                if (json.data && json.data.total != null) return json.data.total;
                if (json.total != null) return json.total;
                if (json.totalCount != null) return json.totalCount;
                return 0;
            }

            function updateServerPagination(json) {
                if (!$paginationBar || !state.pagination) return;
                var total = paginationTotal(json);
                var page = state.pagination.page;
                var rows = state.pagination.rows;
                var pageCount = Math.max(1, Math.ceil(total / rows) || 1);
                if (page > pageCount) {
                    state.pagination.page = pageCount;
                    api.ajax.reload(null, false);
                    return;
                }
                var from = total ? ((page - 1) * rows + 1) : 0;
                var to = Math.min(page * rows, total);
                $paginationBar.find('.sr-tree-page-info').text(
                    from + '–' + to + ' / ' + total
                );
                $paginationBar.find('.sr-tree-page-prev').prop('disabled', page <= 1);
                $paginationBar.find('.sr-tree-page-next').prop('disabled', page >= pageCount);
            }

            function wireServerPagination() {
                if (!opts.serverPagination || !state.pagination) return;
                var sp = opts.serverPagination;
                var pageSizes = sp.pageSizes || [5, 10, 20, 100];
                var $wrapper = $table.closest('.dataTables_wrapper');
                if (!$wrapper.length) {
                    $wrapper = $table.wrap('<div class="dataTables_wrapper"></div>').parent();
                }
                $paginationBar = $('<div class="sr-tree-pagination d-flex align-items-center justify-content-end gap-2 mt-2 flex-wrap"></div>');
                var $rowsSelect = $('<select class="form-select form-select-sm sr-tree-page-size" style="width:auto"></select>');
                for (var i = 0; i < pageSizes.length; i++) {
                    $('<option>').attr('value', pageSizes[i]).text(pageSizes[i]).appendTo($rowsSelect);
                }
                $rowsSelect.val(String(state.pagination.rows));
                var $prev = $('<button type="button" class="btn btn-sm btn-outline-secondary sr-tree-page-prev">&laquo;</button>');
                var $next = $('<button type="button" class="btn btn-sm btn-outline-secondary sr-tree-page-next">&raquo;</button>');
                var $info = $('<span class="sr-tree-page-info small text-muted"></span>');
                $paginationBar.append(
                    $('<span class="small"></span>').text((typeof _lang !== 'undefined' && _lang && _lang['Rows']) ? _lang['Rows'] : 'Rows'),
                    $rowsSelect,
                    $prev,
                    $next,
                    $info
                );
                $wrapper.append($paginationBar);
                $rowsSelect.on('change', function () {
                    state.pagination.rows = parseInt($(this).val(), 10) || sp.pageSize || 10;
                    state.pagination.page = 1;
                    api.ajax.reload();
                });
                $prev.on('click', function () {
                    if (state.pagination.page > 1) {
                        state.pagination.page--;
                        api.ajax.reload();
                    }
                });
                $next.on('click', function () {
                    state.pagination.page++;
                    api.ajax.reload();
                });
            }

            // --- Expand/collapse toggle ---
            // Route through api.srToggle() so lazy trees (rows flagged
            // hasLazyChildren) fetch their children on first expand. For eager
            // trees (children already nested in the payload) srToggle() falls
            // through to a plain visibility toggle, so behaviour is unchanged.
            $table.on('click', '.sr-tree-toggle', function (e) {
                e.preventDefault();
                e.stopPropagation();
                api.srToggle($(this).data('sr-toggle'));
            });
            $table.on('keydown', '.sr-tree-toggle', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });

            // --- Drag-to-reparent (HTML5 DnD via the grip handle) ---
            if (opts.dnd && opts.dnd.reparent) {
                var r = opts.dnd.reparent;

                var doReparent = function (sourceId, targetId) {
                    if (!sourceId || sourceId === '' || sourceId == targetId) return;
                    // Don't allow dropping a node onto its own descendant
                    if (isDescendant(api, sourceId, targetId)) {
                        if (typeof showAlertFromMessage === 'function') {
                            var cantMoveMsg = (opts.lang && opts.lang.CantMoveIntoOwnChild)
                                || (typeof _lang !== 'undefined' && _lang && _lang['CantMoveIntoOwnChild'])
                                || 'Cannot move a node into its own descendant.';
                            showAlertFromMessage(cantMoveMsg, false);
                        }
                        return;
                    }
                    var data = {};
                    data[r.idParam || 'framework_id'] = sourceId;
                    data[r.parentParam || 'parent'] = targetId;
                    $.ajax({
                        url: r.url,
                        type: r.type || 'POST',
                        data: data,
                        success: function (res) {
                            if (res && res.status_message && typeof showAlertsFromArray === 'function') {
                                showAlertsFromArray(res.status_message);
                            }
                            if (r.reload !== false) {
                                api.ajax.reload();
                            }
                        },
                        error: function (xhr) {
                            if (typeof retryCSRF === 'function' && !retryCSRF(xhr, this)) {
                                if (xhr.responseJSON && xhr.responseJSON.status_message && typeof showAlertsFromArray === 'function') {
                                    showAlertsFromArray(xhr.responseJSON.status_message);
                                }
                            }
                        }
                    });
                };

                // True if `descId` is a descendant of `ancId` in the current tree
                var isDescendant = function (apiObj, ancId, descId) {
                    var parents = {}; // id -> parentId
                    apiObj.rows().every(function () {
                        var d = this.data();
                        if (d && d._sr) parents[d._sr.id] = d._sr.parentId;
                    });
                    var cur = parents[descId];
                    while (cur !== null && cur !== undefined && cur !== 0 && cur !== '') {
                        if (cur == ancId) return true;
                        cur = parents[cur];
                    }
                    return false;
                };

                $table.on('dragstart', '.sr-tree-grip', function (e) {
                    var id = $(this).data('sr-grip');
                    e.originalEvent.dataTransfer.setData('text/sr-id', String(id));
                    e.originalEvent.dataTransfer.effectAllowed = 'move';
                });
                $table.on('dragover', 'tr[data-sr-droppable]', function (e) {
                    e.preventDefault();
                    e.originalEvent.dataTransfer.dropEffect = 'move';
                    $(this).addClass('sr-tree-drop-target');
                });
                $table.on('dragleave', 'tr[data-sr-droppable]', function () {
                    $(this).removeClass('sr-tree-drop-target');
                });
                $table.on('drop', 'tr[data-sr-droppable]', function (e) {
                    e.preventDefault();
                    $(this).removeClass('sr-tree-drop-target');
                    var sourceId = e.originalEvent.dataTransfer.getData('text/sr-id');
                    var targetId = $(this).data('sr-id');
                    doReparent(sourceId, targetId);
                });
            }
        });
    };

    // Expose the pure helpers for unit testing (no DataTables dependency needed).
    $.srTree = {
        flattenTree: flattenTree,
        countNodes: countNodes,
        computeVisibility: computeVisibility,
        mergeLazyChildren: mergeLazyChildren
    };

    // Reload a tree table if initialized (no-op otherwise).
    $.fn.reloadSrTree = function () {
        return this.each(function () {
            var $t = $(this);
            if ($.fn.dataTable.isDataTable($t)) {
                $t.DataTable().ajax.reload(null, false);
            }
        });
    };
})(jQuery);

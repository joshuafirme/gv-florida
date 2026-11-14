(function (window, document) {
    'use strict';

    class FleetSeatLayoutEditor {
        constructor(root, options) {
            this.root = root;
            this.options = options || {};
            this.layout = null;
            this.fallbackLayout = null;
            this.deckIndex = 0;
            this.selected = null;
            this.dragSource = null;
            this.bind();
        }

        clone(value) {
            return value ? JSON.parse(JSON.stringify(value)) : null;
        }

        bind() {
            this.root.querySelector('[data-action="customize"]').addEventListener('click', () => this.open());
            this.root.querySelector('[data-action="reset"]').addEventListener('click', () => this.reset());
            this.root.querySelector('[data-action="add-row"]').addEventListener('click', () => this.addRow());
            this.root.querySelector('[data-action="remove-row"]').addEventListener('click', () => this.removeRow());
            this.root.querySelector('[data-action="add-column"]').addEventListener('click', () => this.addColumn());
            this.root.querySelector('[data-action="remove-column"]').addEventListener('click', () => this.removeColumn());
            this.root.querySelector('[data-action="number-ltr"]').addEventListener('click', () => this.autoNumber(false));
            this.root.querySelector('[data-action="number-rtl"]').addEventListener('click', () => this.autoNumber(true));
            this.root.querySelector('[data-action="move-up"]').addEventListener('click', () => this.moveSelected(-1, 0));
            this.root.querySelector('[data-action="move-down"]').addEventListener('click', () => this.moveSelected(1, 0));
            this.root.querySelector('[data-action="move-left"]').addEventListener('click', () => this.moveSelected(0, -1));
            this.root.querySelector('[data-action="move-right"]').addEventListener('click', () => this.moveSelected(0, 1));

            this.root.querySelector('[data-field="type"]').addEventListener('change', (event) => {
                const cell = this.selectedCell();
                if (!cell) return;
                const nextType = event.target.value;
                if (nextType === 'seat' && cell.type !== 'seat') {
                    const label = this.nextSeatLabel();
                    Object.assign(cell, {
                        type: 'seat',
                        label,
                        id: `${this.deckIndex + 1}-${label}`,
                        aliases: [],
                        priority: false
                    });
                } else if (nextType === 'cr') {
                    Object.keys(cell).forEach(key => delete cell[key]);
                    Object.assign(cell, { type: 'cr', label: 'CR', span: 1, row_span: 1 });
                } else if (nextType !== 'seat') {
                    Object.keys(cell).forEach(key => delete cell[key]);
                    cell.type = nextType;
                }
                this.changed();
            });

            this.root.querySelector('[data-field="label"]').addEventListener('input', (event) => {
                const cell = this.selectedCell();
                if (!cell || cell.type !== 'seat') return;
                const label = String(event.target.value || '').toUpperCase().replace(/[^A-Z0-9_-]/g, '').slice(0, 20);
                if (!label || label === cell.label) return;
                cell.aliases = Array.from(new Set([...(cell.aliases || []), cell.id, cell.label].filter(Boolean)));
                cell.label = label;
                cell.id = `${this.deckIndex + 1}-${label}`;
                this.changed(false);
            });

            this.root.querySelector('[data-field="priority"]').addEventListener('change', (event) => {
                const cell = this.selectedCell();
                if (!cell || cell.type !== 'seat') return;
                cell.priority = event.target.checked;
                this.changed();
            });

            ['span', 'row-span'].forEach(field => {
                this.root.querySelector(`[data-field="${field}"]`).addEventListener('input', (event) => {
                    const cell = this.selectedCell();
                    if (!cell || cell.type !== 'cr') return;
                    const key = field === 'row-span' ? 'row_span' : 'span';
                    cell[key] = Math.max(1, Math.min(parseInt(event.target.value, 10) || 1, field === 'row-span' ? 10 : 6));
                    this.changed();
                });
            });
        }

        setFallback(layout) {
            this.fallbackLayout = this.clone(layout);
        }

        setSaved(layout) {
            this.layout = this.clone(layout);
            this.deckIndex = 0;
            this.selected = null;
            this.root.querySelector('[data-action="reset"]').disabled = !this.layout;
            if (!this.layout) {
                this.root.querySelector('[data-role="editor"]').classList.add('d-none');
                this.root.querySelector('[data-role="preview"]').classList.remove('d-none');
            }
            if (this.isOpen()) this.render();
        }

        open() {
            if (!this.layout) this.layout = this.clone(this.fallbackLayout);
            if (!this.layout || !Array.isArray(this.layout.decks) || !this.layout.decks.length) return;
            this.root.querySelector('[data-role="editor"]').classList.remove('d-none');
            this.root.querySelector('[data-role="preview"]').classList.add('d-none');
            this.root.querySelector('[data-action="reset"]').disabled = false;
            this.render();
        }

        reset() {
            this.layout = null;
            this.selected = null;
            this.deckIndex = 0;
            this.root.querySelector('[data-role="editor"]').classList.add('d-none');
            this.root.querySelector('[data-role="preview"]').classList.remove('d-none');
            this.root.querySelector('[data-action="reset"]').disabled = true;
            if (this.options.onChange) this.options.onChange(null);
        }

        isOpen() {
            return !this.root.querySelector('[data-role="editor"]').classList.contains('d-none');
        }

        currentDeck() {
            return this.layout && this.layout.decks ? this.layout.decks[this.deckIndex] : null;
        }

        selectedCell() {
            const deck = this.currentDeck();
            if (!deck || !this.selected) return null;
            return deck.rows[this.selected.row] && deck.rows[this.selected.row][this.selected.column];
        }

        render() {
            const deck = this.currentDeck();
            if (!deck) return;
            const tabs = this.root.querySelector('[data-role="deck-tabs"]');
            tabs.innerHTML = '';
            this.layout.decks.forEach((item, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = `seat-layout-editor__deck-tab${index === this.deckIndex ? ' is-active' : ''}`;
                button.textContent = item.name || `Deck ${index + 1}`;
                button.addEventListener('click', () => {
                    this.deckIndex = index;
                    this.selected = null;
                    this.render();
                });
                tabs.appendChild(button);
            });

            const grid = this.root.querySelector('[data-role="grid"]');
            const columns = Math.max(...deck.rows.map(row => row.length), 1);
            grid.style.gridTemplateColumns = `repeat(${columns}, 54px)`;
            grid.innerHTML = '';

            deck.rows.forEach((row, rowIndex) => {
                row.forEach((cell, columnIndex) => {
                    const element = document.createElement('button');
                    element.type = 'button';
                    element.className = this.cellClass(cell, rowIndex, columnIndex);
                    element.textContent = this.cellLabel(cell);
                    element.dataset.row = rowIndex;
                    element.dataset.column = columnIndex;
                    element.draggable = true;
                    element.title = cell.type === 'seat' ? `Seat ${cell.label}` : this.cellLabel(cell);
                    element.addEventListener('click', () => {
                        this.selected = { row: rowIndex, column: columnIndex };
                        this.render();
                    });
                    element.addEventListener('dragstart', (event) => {
                        this.dragSource = { row: rowIndex, column: columnIndex };
                        event.dataTransfer.effectAllowed = 'move';
                        element.classList.add('is-dragging');
                    });
                    element.addEventListener('dragend', () => element.classList.remove('is-dragging'));
                    element.addEventListener('dragover', event => event.preventDefault());
                    element.addEventListener('drop', (event) => {
                        event.preventDefault();
                        if (!this.dragSource) return;
                        this.swap(this.dragSource.row, this.dragSource.column, rowIndex, columnIndex);
                        this.dragSource = null;
                    });
                    grid.appendChild(element);
                });
            });

            this.renderProperties();
            this.renderCount();
        }

        cellClass(cell, row, column) {
            const selected = this.selected && this.selected.row === row && this.selected.column === column;
            return [
                'seat-layout-editor__cell',
                `seat-layout-editor__cell--${cell.type || 'empty'}`,
                selected ? 'is-selected' : '',
                cell.priority ? 'is-priority' : ''
            ].filter(Boolean).join(' ');
        }

        cellLabel(cell) {
            if (cell.type === 'seat') return cell.label || 'Seat';
            if (cell.type === 'cr') return 'CR';
            if (cell.type === 'aisle') return 'Aisle';
            if (cell.type === 'covered') return 'Covered';
            return 'Empty';
        }

        renderProperties() {
            const cell = this.selectedCell();
            const properties = this.root.querySelector('[data-role="properties"]');
            properties.classList.toggle('d-none', !cell);
            if (!cell) return;
            properties.querySelector('[data-field="type"]').value = cell.type;
            const isSeat = cell.type === 'seat';
            const isCr = cell.type === 'cr';
            properties.querySelector('[data-wrap="label"]').classList.toggle('d-none', !isSeat);
            properties.querySelector('[data-wrap="priority"]').classList.toggle('d-none', !isSeat);
            properties.querySelector('[data-wrap="cr-span"]').classList.toggle('d-none', !isCr);
            properties.querySelector('[data-wrap="cr-row-span"]').classList.toggle('d-none', !isCr);
            properties.querySelector('[data-field="label"]').value = cell.label || '';
            properties.querySelector('[data-field="priority"]').checked = Boolean(cell.priority);
            properties.querySelector('[data-field="span"]').value = cell.span || 1;
            properties.querySelector('[data-field="row-span"]').value = cell.row_span || 1;
        }

        renderCount() {
            const counts = this.layout.decks.map(deck => deck.rows.reduce(
                (total, row) => total + row.filter(cell => cell.type === 'seat').length,
                0
            ));
            const expected = this.options.expectedCounts ? this.options.expectedCounts() : [];
            const actual = counts[this.deckIndex] || 0;
            const target = expected[this.deckIndex];
            this.root.querySelector('[data-role="seat-count"]').textContent = target == null
                ? `${actual} seats`
                : `${actual} / ${target} seats`;
        }

        swap(fromRow, fromColumn, toRow, toColumn) {
            const rows = this.currentDeck().rows;
            const temporary = rows[fromRow][fromColumn];
            rows[fromRow][fromColumn] = rows[toRow][toColumn];
            rows[toRow][toColumn] = temporary;
            this.selected = { row: toRow, column: toColumn };
            this.changed();
        }

        moveSelected(rowDelta, columnDelta) {
            if (!this.selected) return;
            const rows = this.currentDeck().rows;
            const targetRow = this.selected.row + rowDelta;
            const targetColumn = this.selected.column + columnDelta;
            if (!rows[targetRow] || !rows[targetRow][targetColumn]) return;
            this.swap(this.selected.row, this.selected.column, targetRow, targetColumn);
        }

        addRow() {
            const deck = this.currentDeck();
            const columns = deck.rows[0] ? deck.rows[0].length : 1;
            deck.rows.push(Array.from({ length: columns }, () => ({ type: 'empty' })));
            this.selected = null;
            this.changed();
        }

        removeRow() {
            const deck = this.currentDeck();
            if (deck.rows.length <= 1) return;
            deck.rows.pop();
            this.selected = null;
            this.changed();
        }

        addColumn() {
            const deck = this.currentDeck();
            if ((deck.rows[0] || []).length >= 12) return;
            deck.rows.forEach(row => row.push({ type: 'empty' }));
            this.selected = null;
            this.changed();
        }

        removeColumn() {
            const deck = this.currentDeck();
            if ((deck.rows[0] || []).length <= 1) return;
            deck.rows.forEach(row => row.pop());
            this.selected = null;
            this.changed();
        }

        autoNumber(reverse) {
            const prefix = String(this.root.querySelector('[data-field="number-prefix"]').value || '').toUpperCase();
            let number = 1;
            this.currentDeck().rows.forEach(row => {
                const cells = reverse ? [...row].reverse() : row;
                cells.forEach(cell => {
                    if (cell.type !== 'seat') return;
                    const label = `${prefix}${number++}`;
                    if (cell.label !== label) {
                        cell.aliases = Array.from(new Set([...(cell.aliases || []), cell.id, cell.label].filter(Boolean)));
                        cell.label = label;
                        cell.id = `${this.deckIndex + 1}-${label}`;
                    }
                });
            });
            this.changed();
        }

        nextSeatLabel() {
            const used = new Set(this.layout.decks.flatMap(deck => deck.rows.flatMap(row => row
                .filter(cell => cell.type === 'seat')
                .map(cell => cell.label))));
            let number = 1;
            while (used.has(String(number))) number++;
            return String(number);
        }

        changed(render = true) {
            this.layout.version = 1;
            if (render) this.render();
            if (this.options.onChange) this.options.onChange(this.clone(this.layout));
        }
    }

    window.FleetSeatLayoutEditor = FleetSeatLayoutEditor;
})(window, document);

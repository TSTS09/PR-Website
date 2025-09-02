</main>
    </div>
    
    <!-- Additional Scripts -->
    <script src="../assets/js/main.js"></script>
    <?php if (isset($additionalScripts)): ?>
        <?php echo $additionalScripts; ?>
    <?php endif; ?>
    
    <script>
        // Re-initialize Lucide icons after dynamic content loads
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
        
        // Global utility functions for admin panel
        window.AdminUtils = {
            // Format date for display
            formatDate: function(dateString, options = {}) {
                const defaultOptions = {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                };
                
                return new Date(dateString).toLocaleDateString('en-US', {
                    ...defaultOptions,
                    ...options
                });
            },
            
            // Truncate text
            truncate: function(text, maxLength = 100) {
                if (!text || text.length <= maxLength) return text;
                return text.substring(0, maxLength).trim() + '...';
            },
            
            // Copy to clipboard
            copyToClipboard: function(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(() => {
                        AdminPanel.showNotification('Copied to clipboard!', 'success');
                    });
                } else {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    
                    try {
                        document.execCommand('copy');
                        AdminPanel.showNotification('Copied to clipboard!', 'success');
                    } catch (err) {
                        AdminPanel.showNotification('Failed to copy to clipboard', 'error');
                    }
                    
                    document.body.removeChild(textArea);
                }
            },
            
            // Initialize data tables with sorting and filtering
            initDataTable: function(tableSelector, options = {}) {
                const table = document.querySelector(tableSelector);
                if (!table) return;
                
                // Add sorting functionality
                const headers = table.querySelectorAll('th[data-sort]');
                headers.forEach(header => {
                    header.style.cursor = 'pointer';
                    header.addEventListener('click', () => {
                        this.sortTable(table, header);
                    });
                });
                
                // Add search functionality if search input exists
                const searchInput = document.querySelector(`${tableSelector}-search`);
                if (searchInput) {
                    searchInput.addEventListener('input', (e) => {
                        this.filterTable(table, e.target.value);
                    });
                }
            },
            
            // Sort table by column
            sortTable: function(table, header) {
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                const isAscending = header.classList.contains('sort-asc');
                
                // Remove all sort classes
                header.parentNode.querySelectorAll('th').forEach(th => {
                    th.classList.remove('sort-asc', 'sort-desc');
                });
                
                // Add appropriate sort class
                header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
                
                // Sort rows
                rows.sort((a, b) => {
                    const aText = a.cells[columnIndex].textContent.trim().toLowerCase();
                    const bText = b.cells[columnIndex].textContent.trim().toLowerCase();
                    
                    // Try to parse as numbers first
                    const aNum = parseFloat(aText);
                    const bNum = parseFloat(bText);
                    
                    if (!isNaN(aNum) && !isNaN(bNum)) {
                        return isAscending ? bNum - aNum : aNum - bNum;
                    }
                    
                    // Text comparison
                    if (isAscending) {
                        return bText.localeCompare(aText);
                    } else {
                        return aText.localeCompare(bText);
                    }
                });
                
                // Rebuild tbody
                tbody.innerHTML = '';
                rows.forEach(row => tbody.appendChild(row));
            },
            
            // Filter table rows
            filterTable: function(table, searchTerm) {
                const tbody = table.querySelector('tbody');
                const rows = tbody.querySelectorAll('tr');
                const term = searchTerm.toLowerCase();
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                });
            },
            
            // Export table data as CSV
            exportTableCSV: function(tableSelector, filename = 'export.csv') {
                const table = document.querySelector(tableSelector);
                if (!table) return;
                
                const rows = table.querySelectorAll('tr');
                const csv = [];
                
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td, th');
                    const rowData = Array.from(cells).map(cell => {
                        // Clean up cell content (remove HTML, extra whitespace)
                        return '"' + cell.textContent.replace(/"/g, '""').trim() + '"';
                    });
                    csv.push(rowData.join(','));
                });
                
                // Download CSV
                const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement('a');
                link.setAttribute('href', encodedUri);
                link.setAttribute('download', filename);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                AdminPanel.showNotification('Data exported successfully!', 'success');
            }
        };
    </script>
</body>
</html>
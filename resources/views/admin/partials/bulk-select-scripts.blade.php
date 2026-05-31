<script>
    document.addEventListener("DOMContentLoaded", () => {
        const form = document.querySelector("{{ $form }}");

        if (! form) {
            return;
        }

        const selectAll = form.querySelector("{{ $selectAll }}");
        const rowChecks = Array.from(form.querySelectorAll("{{ $rowCheck }}"));
        const selectedCount = form.querySelector("{{ $selectedCount }}");
        const itemName = "{{ $itemName ?? 'item' }}";
        const updateSelectionState = () => {
            const count = rowChecks.filter((checkbox) => checkbox.checked).length;

            if (selectedCount) {
                selectedCount.textContent = `${count} selected`;
            }

            if (selectAll) {
                selectAll.checked = count > 0 && count === rowChecks.length;
                selectAll.indeterminate = count > 0 && count < rowChecks.length;
            }
        };

        selectAll?.addEventListener("change", () => {
            rowChecks.forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
            updateSelectionState();
        });

        rowChecks.forEach((checkbox) => {
            checkbox.addEventListener("change", updateSelectionState);
        });

        form.addEventListener("submit", (event) => {
            if (rowChecks.length > 0 && ! rowChecks.some((checkbox) => checkbox.checked)) {
                event.preventDefault();

                const message = `Select at least one ${itemName} before applying a bulk action.`;

                if (window.notify?.error) {
                    window.notify.error(message);
                } else {
                    window.alert(message);
                }
            }
        });

        updateSelectionState();
    });
</script>

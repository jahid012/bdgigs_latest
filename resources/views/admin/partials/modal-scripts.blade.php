<script>
    document.addEventListener("DOMContentLoaded", () => {
        document.querySelectorAll("[data-admin-modal-open]").forEach((button) => {
            button.addEventListener("click", () => {
                const modal = document.getElementById(button.dataset.adminModalOpen);

                if (modal?.showModal) {
                    modal.showModal();
                }
            });
        });

        document.querySelectorAll("[data-admin-modal-close]").forEach((button) => {
            button.addEventListener("click", () => {
                button.closest("[data-admin-modal]")?.close();
            });
        });

        document.querySelectorAll("[data-admin-modal]").forEach((modal) => {
            modal.addEventListener("click", (event) => {
                if (event.target === modal) {
                    modal.close();
                }
            });
        });
    });
</script>

</div> <!-- End main-content -->

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- Custom Admin JS -->
<script>
    // Sidebar Toggle
    $(document).ready(function() {
        $('#sidebarToggle').click(function() {
            $('.sidebar').toggleClass('collapsed');
            $('.main-content').toggleClass('expanded');
        });

        // Initialize DataTables
        if ($('.datatable').length) {
            $('.datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
                },
                pageLength: 10,
                ordering: true,
                searching: true
            });
        }
    });
</script>

<footer class="footer">
    <div class="container-fluid">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> TripGo. All rights reserved.</p>
    </div>
</footer>

</body>
</html>

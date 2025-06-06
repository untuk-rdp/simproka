    </main>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>SIMPROKA TBS</h5>
                    <p class="mb-0">Sistem monitoring program kerja</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0"><a href="https://youtube.com/@hanyauntukmu"> &copy; <?= date('Y') ?> Divisi ITPM Yayasan TBS.</a> All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?= base_url('assets/js/main.js') ?>"></script>
    <?php if (isset($jsFile)): ?>
        <script src="<?= base_url('assets/js/' . $jsFile) ?>"></script>
    <?php endif; ?>
</body>
</html>
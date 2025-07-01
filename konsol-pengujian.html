<script>
    // Simulasi notifikasi untuk pengujian
    function addNotification(type, title, message) {
        const loggedInUser = localStorage.getItem('loggedInUser');
        if (!loggedInUser) {
            console.error("Tidak ada pengguna yang login. Silakan login terlebih dahulu.");
            return;
        }

        const notificationKey = 'userNotifications_' + loggedInUser;
        let notifications = JSON.parse(localStorage.getItem(notificationKey) || '[]');

        notifications.push({
            type,
            title,
            message,
            timestamp: new Date().toISOString(),
            read: false // Notifikasi baru selalu belum dibaca
        });
        localStorage.setItem(notificationKey, JSON.stringify(notifications));
        console.log(`Notifikasi baru ditambahkan: "${title}"`);

        // Perbarui tampilan dot notifikasi di navbar
        const notificationDot = document.querySelector('.notification-dot');
        if (notificationDot) {
            notificationDot.style.display = 'block';
        }
    }

    addNotification(
        'new_assignment',
        'Penugasan Baru!',
        'Anda memiliki penugasan baru: "Bersih-bersih Pantai" pada 10 Juli 2025. Cek detail penugasan Anda!'
    );
    addNotification(
        'new_evaluation',
        'Evaluasi Baru Tersedia!',
        'Riwayat evaluasi terbaru Anda untuk kegiatan "Donasi Bencana" sudah tersedia. Lihat riwayat evaluasi Anda!'
    );
    addNotification(
        'coordinator_appointment',
        'Selamat! Anda Ditunjuk Sebagai Koordinator!',
        'Anda telah ditunjuk sebagai koordinator lapangan untuk kegiatan "Penanaman Pohon". Silakan cek email Anda untuk detail lebih lanjut.'
    );
    addNotification(
        'general',
        'Pembaruan Penting!',
        'Ada pembaruan kebijakan privasi. Mohon baca selengkapnya di bagian Pengaturan.'
    );

    // Pengujian Detail Penugasan
    // Fungsi untuk menambahkan penugasan baru ke localStorage
    function addAssignment(assignment) {
        const loggedInUser = localStorage.getItem('loggedInUser');
        if (!loggedInUser) {
            console.error("Tidak ada pengguna yang login. Silakan login terlebih dahulu.");
            return;
        }

        const userAssignmentsKey = 'userAssignments_' + loggedInUser;
        let userAssignments = JSON.parse(localStorage.getItem(userAssignmentsKey) || '[]');

        // Periksa apakah penugasan dengan ID yang sama sudah ada untuk mencegah duplikasi
        if (!userAssignments.some(a => a.id === assignment.id)) {
            userAssignments.push(assignment);
            localStorage.setItem(userAssignmentsKey, JSON.stringify(userAssignments));
            console.log(`Penugasan "${assignment.title}" (ID: ${assignment.id}) berhasil ditambahkan.`);
        } else {
            console.warn(`Penugasan dengan ID "${assignment.id}" sudah ada. Tidak ditambahkan.`);
        }
    }

    const newAssignment4 = {
        id: 'assign-004',
        title: 'Kampanye Kebersihan Lingkungan',
        date: '2025-06-25', // Format YYYY-MM-DD
        time: '09:00 - 16:00',
        location: 'Taman Kota, Jakarta Pusat',
        coordinator: 'Ibu Dian Lestari (0813-9876-5432)',
        description: 'Mengadakan kampanye kebersihan dan edukasi tentang pengelolaan sampah di area taman kota. Relawan akan dibagi menjadi tim pemungut sampah, tim edukasi, dan tim daur ulang.',
        equipment: '- Sarung tangan, kantong sampah, dan alat kebersihan disediakan.<br>- Disarankan membawa topi dan botol minum pribadi.'
    };
    addAssignment(newAssignment4); // Jalankan ini di konsol

    const newAssignment5 = {
        id: 'assign-005',
        title: 'Bantuan Logistik Bencana Alam',
        date: '2025-10-20',
        time: 'Sepanjang Hari',
        location: 'Posko Bantuan, Cianjur',
        coordinator: 'Bapak Heru Susanto (0857-1234-5678)',
        description: 'Membantu proses sortir, pengepakan, dan distribusi bantuan logistik (makanan, pakaian, obat-obatan) untuk korban bencana alam. Diperlukan fisik yang prima dan kesiapan untuk bekerja di lapangan.',
        equipment: '- Pakaian lapangan yang nyaman.<br>- Makanan dan minuman disediakan di posko.<br>- Obat-obatan pribadi jika diperlukan.'
    };
    addAssignment(newAssignment5); // Jalankan ini di konsol

    const newAssignment6 = {
        id: 'assign-006',
        title: 'Donor Darah Rutin',
        date: '2025-11-05',
        time: '09:00 - 15:00',
        location: 'Puskesmas Sehat Selalu, Bandung',
        coordinator: 'Dr. Rina Amelia (0812-5678-9012)',
        description: 'Membantu proses pendaftaran donor, mengarahkan peserta, dan menjaga kenyamanan area donor darah. Pastikan relawan dalam kondisi sehat.',
        equipment: '- Pakaian rapi.<br>- Sarapan cukup sebelum bertugas.'
    };
    addAssignment(newAssignment6); // Jalankan ini di konsol

    const newAssignment7 = {
        id: 'assign-007',
        title: 'Pelatihan Pertolongan Pertama',
        date: '2025-12-01',
        time: '13:00 - 17:00',
        location: 'Aula Serbaguna, Surabaya',
        coordinator: 'Tim Medis CivicaCare (0877-2345-6789)',
        description: 'Mengikuti pelatihan dasar pertolongan pertama untuk relawan. Materi meliputi penanganan luka, CPR, dan evakuasi dasar.',
        equipment: '- Catatan dan alat tulis.<br>- Pakaian nyaman untuk praktik.'
    };
    addAssignment(newAssignment7); // Jalankan ini di konsol

</script>
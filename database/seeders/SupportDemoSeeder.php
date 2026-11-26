<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Faq;
use App\Models\Message;
use App\Models\Product;
use Illuminate\Database\Seeder;

class SupportDemoSeeder extends Seeder
{
    public function run(): void
    {
        $series = [
            ['Apple', 'MacBook Air M2 13.6-inch', 'Kerja & Kuliah', 'Apple M2 8-core CPU', 'Apple GPU 8-core', '13.6 inci Liquid Retina', 13999000, 'Ringan dengan layar Liquid Retina untuk belajar dan produktivitas.'],
            ['Apple', 'MacBook Air M3 13.6-inch', 'Kerja & Kuliah', 'Apple M3 8-core CPU', 'Apple GPU 10-core', '13.6 inci Liquid Retina', 16999000, 'Laptop tipis dengan chip Apple M3 dan layar Liquid Retina.'],
            ['ASUS', 'Vivobook Go 14 E1404FA', 'Kerja & Kuliah', 'AMD Ryzen 5 7520U', 'AMD Radeon 610M', '14 inci Full HD IPS', 6999000, 'Laptop ringkas untuk kelas online dan tugas harian.'],
            ['ASUS', 'TUF Gaming A15 FA507NV', 'Gaming', 'AMD Ryzen 7 7735HS', 'NVIDIA GeForce RTX 4060 8 GB', '15.6 inci Full HD 144 Hz', 17999000, 'Laptop gaming dengan grafis RTX dan layar refresh rate tinggi.'],
            ['Lenovo', 'IdeaPad Slim 3 14IRU8', 'Kerja & Kuliah', 'Intel Core i5-1335U', 'Intel Iris Xe Graphics', '14 inci Full HD IPS', 7499000, 'Laptop harian tipis untuk belajar dan produktivitas.'],
            ['Acer', 'Aspire 5 A515-58M', 'Kerja & Kuliah', 'Intel Core i5-1335U', 'Intel Iris Xe Graphics', '15.6 inci Full HD IPS', 8299000, 'Layar lega untuk produktivitas, kuliah, dan hiburan.'],
            ['HP', 'Victus 15-fa2700TX', 'Gaming', 'Intel Core i5-13420H', 'NVIDIA GeForce RTX 4050 6 GB', '15.6 inci Full HD 144 Hz', 15499000, 'Laptop gaming dengan grafis diskrit untuk game modern.'],
            ['Dell', 'Inspiron 14 5430', 'Bisnis', 'Intel Core i7-1360P', 'Intel Iris Xe Graphics', '14 inci 2.2K IPS', 13999000, 'Laptop bisnis dengan layar tajam dan bodi ringkas.'],
            ['MSI', 'Thin 15 B13VE', 'Gaming', 'Intel Core i7-13620H', 'NVIDIA GeForce RTX 4050 6 GB', '15.6 inci Full HD 144 Hz', 16499000, 'Bodi tipis dengan grafis diskrit untuk gaming dan kreasi.'],
            ['Axioo', 'Pongo 760', 'Kreator & Gaming', 'Intel Core i7-13620H', 'NVIDIA GeForce RTX 4060 8 GB', '16 inci QHD 165 Hz', 18499000, 'Layar luas dan grafis diskrit untuk kreator serta gaming.'],
        ];
        $configurations = [
            ['8 GB', '256 GB SSD', 0],
            ['8 GB', '512 GB SSD', 500000],
            ['16 GB', '256 GB SSD', 750000],
            ['16 GB', '512 GB SSD', 1250000],
            ['16 GB', '1 TB SSD', 2250000],
            ['32 GB', '512 GB SSD', 2750000],
            ['32 GB', '1 TB SSD', 3500000],
            ['8 GB', '1 TB SSD', 1550000],
            ['16 GB', '2 TB SSD', 3500000],
            ['32 GB', '2 TB SSD', 5000000],
        ];
        $macbookConfigurations = [
            ['8 GB', '256 GB SSD', 0],
            ['8 GB', '512 GB SSD', 600000],
            ['8 GB', '1 TB SSD', 1500000],
            ['16 GB', '256 GB SSD', 2000000],
            ['16 GB', '512 GB SSD', 2600000],
            ['16 GB', '1 TB SSD', 3500000],
            ['16 GB', '2 TB SSD', 5500000],
            ['24 GB', '512 GB SSD', 4800000],
            ['24 GB', '1 TB SSD', 5700000],
            ['24 GB', '2 TB SSD', 7600000],
        ];

        foreach ($series as $seriesIndex => [$brand, $model, $category, $processor, $graphics, $screen, $basePrice, $description]) {
            $modelConfigurations = $brand === 'Apple' ? $macbookConfigurations : $configurations;

            foreach ($modelConfigurations as $configurationIndex => [$ram, $storage, $priceAdjustment]) {
                $number = ($seriesIndex * count($configurations)) + $configurationIndex + 1;

                Product::updateOrCreate(
                    ['sku' => sprintf('LAP-%03d', $number)],
                    [
                        'name' => "{$brand} {$model} {$ram} / {$storage}",
                        'brand' => $brand,
                        'category' => $category,
                        'processor' => $processor,
                        'ram' => $ram,
                        'storage' => $storage,
                        'graphics' => $graphics,
                        'screen' => $screen,
                        'price' => $basePrice + $priceAdjustment,
                        'stock' => 2 + (($seriesIndex * 7 + $configurationIndex * 3) % 16),
                        'description' => "Laptop {$category}. {$description} Garansi resmi 12 bulan.",
                        'is_active' => true,
                    ],
                );
            }
        }

        Product::where('sku', 'like', 'MD-%')->where('brand', 'Microdata')->delete();

        $faqs = [
            ['Laptop mana yang cocok untuk kuliah dan kerja harian?', 'Untuk kuliah dan kerja harian, bandingkan laptop kategori Kerja & Kuliah berdasarkan prosesor, RAM, kapasitas SSD, ukuran layar, dan harga yang tampil di katalog.', 'kuliah, kerja, harian, rekomendasi, laptop'],
            ['Laptop apa yang tersedia untuk gaming?', 'Katalog memiliki beberapa laptop kategori Gaming. Periksa tipe GPU, prosesor, RAM, dan refresh rate layar pada detail masing-masing laptop.', 'gaming, game, gpu, grafis, laptop'],
            ['Berapa harga laptop termurah?', 'Harga mengikuti konfigurasi laptop yang tersedia. Saya dapat mencarikan pilihan paling terjangkau jika Anda menyebutkan kebutuhan dan batas anggaran.', 'harga, termurah, murah, anggaran, budget'],
            ['Apa perbedaan RAM 8 GB dan 16 GB?', 'RAM 8 GB sesuai untuk kebutuhan dasar dan multitasking ringan. RAM 16 GB lebih nyaman untuk banyak aplikasi sekaligus, pengolahan foto, dan pekerjaan yang lebih berat.', 'ram, memori, 8 gb, 16 gb, multitasking'],
            ['Berapa kapasitas penyimpanan laptop?', 'Kapasitas SSD setiap laptop tercantum pada detail produk. Katalog demo menyediakan pilihan konfigurasi mulai dari 256 GB hingga 2 TB.', 'ssd, storage, penyimpanan, kapasitas'],
            ['Apakah laptop memiliki garansi?', 'Laptop pada katalog demo dilengkapi garansi resmi 12 bulan. Simpan bukti pembelian untuk proses klaim garansi.', 'garansi, klaim, servis, laptop'],
            ['Laptop mana yang cocok untuk desain?', 'Untuk desain, pertimbangkan RAM minimal 16 GB, layar beresolusi tinggi, dan GPU sesuai aplikasi yang digunakan. Beri tahu aplikasi atau anggaran Anda agar CS dapat membantu menyaring katalog.', 'desain, kreator, editing, gpu, layar'],
            ['Bisakah saya membandingkan spesifikasi laptop?', 'Bisa. Sebutkan dua nama laptop atau kebutuhan Anda. Saya akan membandingkan prosesor, RAM, SSD, grafis, layar, harga, dan stok dari katalog.', 'bandingkan, perbandingan, spesifikasi, spek'],
            ['Bagaimana cara mengecek stok laptop?', 'Stok yang tercantum pada kartu laptop adalah jumlah pada katalog demo. Tanyakan nama atau SKU laptop ke CS untuk melihat ketersediaan yang tercatat.', 'stok, tersedia, sku, laptop'],
            ['Bagaimana cara bertanya tentang laptop ke customer service?', 'Klik tombol Tanya CS pada halaman toko untuk bertanya tentang harga, spesifikasi, stok, garansi, atau rekomendasi laptop.', 'kontak, cs, bantuan, rekomendasi'],
            ['Apa perbedaan MacBook Air M2 dan M3?', 'Di katalog tersedia MacBook Air dengan chip M2 dan M3. Saya bisa bantu bandingkan konfigurasi RAM, SSD, ukuran layar, dan harga yang tertera.', 'macbook, apple, m2, m3, beda, bandingkan'],
            ['Apakah MacBook cocok untuk kuliah?', 'Katalog memiliki MacBook Air M2 dan M3 kategori Kerja & Kuliah. Sebutkan aplikasi kampus dan anggaran agar saya bantu cocokkan konfigurasinya.', 'macbook, kuliah, mahasiswa, kampus'],
            ['Berapa lama baterai laptop bisa bertahan?', 'Daya tahan baterai belum dicatat pada katalog demo ini. Sebutkan model yang ingin diketahui, nanti CS bantu cek informasi resminya.', 'baterai, battery, tahan, lama, daya'],
            ['Apakah RAM laptop bisa di-upgrade?', 'Informasi RAM yang bisa di-upgrade belum tersedia untuk semua model di katalog. Kirim nama atau SKU laptop agar CS bisa bantu cek detail modelnya.', 'upgrade, ram, tambah ram, memori'],
            ['Apakah SSD laptop bisa diganti atau ditambah?', 'Katalog menampilkan kapasitas SSD bawaan, tetapi jumlah slot dan opsi upgrade belum dicatat. Sebutkan modelnya untuk pengecekan lebih lanjut.', 'upgrade, ssd, tambah ssd, ganti ssd, slot'],
            ['Apakah laptop sudah termasuk Windows?', 'Informasi sistem operasi belum tercatat pada katalog demo. CS dapat membantu memastikan sistem operasi bawaan untuk model yang Anda pilih.', 'windows, os, sistem operasi, lisensi'],
            ['Apakah laptop bisa digunakan untuk Office dan Zoom?', 'Beri tahu aplikasi dan cara pemakaian Anda, misalnya Office saja atau sambil membuka banyak aplikasi. Saya bantu bandingkan prosesor dan RAM dari model yang tersedia.', 'office, zoom, meeting, microsoft, aplikasi'],
            ['Berapa berat laptop ini?', 'Berat laptop belum dicatat pada katalog demo. Sebutkan modelnya, lalu CS dapat membantu mengecek spesifikasi resmi.', 'berat, bobot, kilogram, ringan, dibawa'],
            ['Apakah layarnya touchscreen?', 'Fitur touchscreen belum dicatat di katalog ini. Kirim nama atau SKU laptop yang dimaksud agar CS bisa memeriksa spesifikasi lengkapnya.', 'touchscreen, layar sentuh, sentuh'],
            ['Laptop apa yang cocok untuk programming?', 'Untuk programming, kebutuhan bergantung pada tools dan proyek yang dijalankan. Sebutkan software, jenis pekerjaan, serta anggaran agar saya bantu membandingkan prosesor dan RAM.', 'programming, coding, developer, software, programmer'],
            ['Apa arti refresh rate 144 Hz?', 'Refresh rate menunjukkan berapa kali layar memperbarui gambar per detik. Beberapa laptop gaming di katalog mencantumkan layar 144 Hz; model lain mengikuti spesifikasi masing-masing.', 'refresh rate, hz, 144, layar, gaming'],
            ['GPU laptop mana yang cocok untuk game?', 'Pilihan grafis di katalog mencakup Intel Iris Xe, AMD Radeon, dan NVIDIA GeForce RTX. Sebutkan game dan anggaran agar saya bantu bandingkan model yang tersedia.', 'gpu, vga, game, gaming, rtx, grafis'],
        ];
        $faqByQuestion = collect($faqs)->keyBy(fn (array $faq) => $faq[0]);
        $questionPrefixes = ['Boleh tanya, ', 'Saya mau tahu, ', 'Minta info, '];

        foreach ($faqs as [$question, $answer, $keywords]) {
            Faq::updateOrCreate(
                ['question' => $question],
                ['answer' => $answer, 'keywords' => $keywords, 'is_active' => true],
            );

            foreach ($questionPrefixes as $prefix) {
                Faq::updateOrCreate(
                    ['question' => $prefix.$question],
                    ['answer' => $answer, 'keywords' => $keywords, 'is_active' => true],
                );
            }
        }

        $paraphrases = [
            ['Saya lagi cari laptop buat kuliah dan kerja, sebaiknya yang mana?', 'Laptop mana yang cocok untuk kuliah dan kerja harian?'],
            ['Laptop mahasiswa untuk tugas kampus dan kelas online apa?', 'Laptop mana yang cocok untuk kuliah dan kerja harian?'],
            ['Bisa rekomendasikan laptop buat kerja kantor harian?', 'Laptop mana yang cocok untuk kuliah dan kerja harian?'],
            ['Laptop dengan RTX yang tersedia ada apa saja?', 'Laptop apa yang tersedia untuk gaming?'],
            ['Laptop gaming di bawah 15 juta ada?', 'Laptop apa yang tersedia untuk gaming?'],
            ['Saya mau main game, laptop mana yang punya GPU diskrit?', 'GPU laptop mana yang cocok untuk game?'],
            ['M2 atau M3 untuk MacBook Air, bedanya apa?', 'Apa perbedaan MacBook Air M2 dan M3?'],
            ['Baterai MacBook bisa bertahan berapa lama?', 'Berapa lama baterai laptop bisa bertahan?'],
            ['Kalau RAM 8 GB kurang, bisa ditambah jadi 16 GB?', 'Apakah RAM laptop bisa di-upgrade?'],
            ['Laptop dapat Windows original tidak?', 'Apakah laptop sudah termasuk Windows?'],
            ['Untuk coding Python, laptop dengan spek apa?', 'Laptop apa yang cocok untuk programming?'],
            ['Penyimpanan 256 GB cukup atau pilih 512 GB?', 'Berapa kapasitas penyimpanan laptop?'],
        ];

        foreach ($paraphrases as [$question, $baseQuestion]) {
            [, $answer, $keywords] = $faqByQuestion->get($baseQuestion);
            Faq::updateOrCreate(
                ['question' => $question],
                ['answer' => $answer, 'keywords' => $keywords, 'is_active' => true],
            );
        }

        Faq::whereIn('question', [
            'Bagaimana cara melacak pesanan?',
            'Berapa lama waktu pengiriman?',
            'Bagaimana kebijakan pengembalian barang?',
            'Apakah produk memiliki garansi?',
            'Metode pembayaran apa yang tersedia?',
            'Bagaimana cara mengubah alamat pengiriman?',
            'Apakah bisa membatalkan pesanan?',
            'Bagaimana menghubungi customer service?',
            'Apakah harga sudah termasuk pajak?',
            'Apa yang harus dilakukan jika barang rusak?',
        ])->delete();

        $firstNames = ['Alya', 'Bima', 'Citra', 'Dimas', 'Eka', 'Farah', 'Gilang', 'Hana', 'Indra', 'Jihan'];
        $lastNames = ['Pratama', 'Putri', 'Saputra', 'Wijaya', 'Lestari'];
        $customers = [];

        foreach ($firstNames as $firstName) {
            foreach ($lastNames as $lastName) {
                $email = strtolower($firstName.'.'.$lastName).'@example.test';
                $customers[] = Customer::updateOrCreate(
                    ['email' => $email],
                    ['name' => $firstName.' '.$lastName, 'phone' => '08'.str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT), 'segment' => 'Pelanggan reguler'],
                );
            }
        }

        $demoConversations = [
            ['Rekomendasi laptop kuliah', 'Saya mencari laptop untuk kuliah dan kerja harian. Anggaran sekitar Rp8 juta.', 'Bisa. Saya bantu bandingkan pilihan kategori Kerja & Kuliah dari prosesor, RAM, SSD, dan harganya.'],
            ['Pilihan laptop gaming', 'Laptop gaming apa yang tersedia dengan GPU RTX?', 'Katalog menyediakan pilihan gaming dengan GPU RTX 4050 dan RTX 4060. Saya bisa bantu bandingkan sesuai anggaran Anda.'],
            ['Laptop RAM 16 GB', 'Saya butuh laptop RAM 16 GB untuk multitasking.', 'Ada beberapa pilihan RAM 16 GB. Pilihannya mencakup laptop kerja, bisnis, kreator, dan gaming.'],
            ['Cek harga laptop', 'Berapa harga laptop paling terjangkau di katalog?', 'Saya bantu urutkan pilihan laptop dari harga terendah, lalu kita cocokkan dengan kebutuhan Anda.'],
            ['Laptop untuk desain', 'Laptop mana yang cocok untuk desain dan editing?', 'Untuk desain, mari cek kombinasi prosesor, RAM, GPU, dan resolusi layar berdasarkan aplikasi yang Anda gunakan.'],
        ];
        $legacySubjects = [
            'Cek status pengiriman',
            'Info garansi produk',
            'Pertanyaan katalog',
            'Permintaan retur',
            'Metode pembayaran',
        ];

        foreach (array_slice($customers, 0, 5) as $index => $customer) {
            $conversation = $customer->conversations()->whereIn('subject', $legacySubjects)->first();

            if (! $conversation) {
                continue;
            }

            [$subject, $question, $answer] = $demoConversations[$index];
            $conversation->update(['subject' => $subject, 'status' => 'open', 'last_message_at' => now()]);
            $conversation->messages()->where('sender', 'customer')->oldest()->first()?->update(['body' => $question]);
            $conversation->messages()->where('sender', 'assistant')->oldest()->first()?->update(['body' => $answer, 'source' => 'demo']);
        }

        if (Conversation::query()->exists()) {
            return;
        }

        foreach (array_slice($customers, 0, 5) as $index => $customer) {
            [$subject, $question, $answer] = $demoConversations[$index];
            $conversation = Conversation::create([
                'customer_id' => $customer->id,
                'subject' => $subject,
                'status' => 'open',
                'channel' => 'web',
                'last_message_at' => now()->subMinutes($index * 12),
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'sender' => 'customer',
                'body' => $question,
                'source' => null,
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'sender' => 'assistant',
                'body' => $answer,
                'source' => 'demo',
            ]);
        }
    }
}
// ==== LOGIN & BERANDA ====
document.getElementById('enterBtn')?.addEventListener('click', ()=>{
  const name=document.getElementById('nameInput').value.trim();
  const kelas=document.getElementById('classInput').value.trim();
  if(!name || !kelas){ alert('Masukkan nama dan kelas!'); return;}
  // Simpan data di localStorage
  localStorage.setItem('mpi_name',name);
  localStorage.setItem('mpi_class',kelas);
  // Redirect ke beranda
  window.location.href='beranda.html';
});

// Tampilkan nama di beranda
const userName=document.getElementById('userName');
if(userName){
  const name=localStorage.getItem('mpi_name') || 'Pengguna';
  userName.textContent=name;
}

// ==== MATERI INTERAKTIF ====
// Data materi lengkap
const materiData=[
  {title:"Jenis Jaringan",ringkasan:"Memahami jenis jaringan LAN, WAN, MAN.",
    full:`
    <h3>Jenis Jaringan Komputer</h3>
    
    <p>Jaringan komputer adalah kumpulan perangkat komputer yang saling terhubung untuk berbagi data, sumber daya, dan informasi. Jaringan dibedakan berdasarkan cakupan geografis dan fungsinya. Berikut jenis-jenis jaringan:</p>
    
    <h4>1. LAN (Local Area Network)</h4>
    <p>LAN adalah jaringan yang mencakup area kecil, biasanya dalam satu gedung atau ruangan. Contohnya jaringan komputer di sekolah atau kantor kecil.</p>
    <ul>
      <li><b>Kelebihan:</b> Kecepatan tinggi, biaya murah, mudah dikontrol.</li>
      <li><b>Kekurangan:</b> Cakupan terbatas, tidak cocok untuk jarak jauh.</li>
    </ul>

    <h4>2. MAN (Metropolitan Area Network)</h4>
    <p>MAN adalah jaringan yang mencakup area kota atau kampus besar. Biasanya digunakan oleh instansi pemerintah atau kampus universitas untuk menghubungkan beberapa gedung.</p>
    <ul>
      <li><b>Kelebihan:</b> Dapat menghubungkan beberapa LAN, jarak lebih luas.</li>
      <li><b>Kekurangan:</b> Biaya lebih mahal, pengelolaan lebih kompleks.</li>
    </ul>

    <h4>3. WAN (Wide Area Network)</h4>
    <p>WAN adalah jaringan yang mencakup area sangat luas, bahkan antar negara. Contohnya internet adalah jaringan WAN terbesar di dunia.</p>
    <ul>
      <li><b>Kelebihan:</b> Dapat menghubungkan jarak jauh, cakupan global.</li>
      <li><b>Kekurangan:</b> Biaya mahal, kecepatan lebih lambat dibanding LAN.</li>
    </ul>

    <h4>4. PAN (Personal Area Network)</h4>
    <p>PAN adalah jaringan pribadi yang mencakup area sangat kecil, biasanya di sekitar satu orang, seperti koneksi Bluetooth antar perangkat pribadi.</p>
    <ul>
      <li><b>Kelebihan:</b> Mudah dibuat, biaya rendah.</li>
      <li><b>Kekurangan:</b> Cakupan sangat terbatas, hanya untuk perangkat pribadi.</li>
    </ul>

    <h4>Fungsi Jaringan Komputer</h4>
    <ul>
      <li>Berbagi data dan informasi antar perangkat.</li>
      <li>Berbagi sumber daya, misalnya printer dan storage.</li>
      <li>Meningkatkan komunikasi antar pengguna.</li>
      <li>Mendukung akses internet bersama.</li>
    </ul>

    <h4>Contoh Penerapan di Kehidupan Sehari-hari</h4>
    <ul>
      <li>LAN: Jaringan sekolah untuk guru dan siswa.</li>
      <li>MAN: Jaringan antar kampus universitas di satu kota.</li>
      <li>WAN: Internet yang menghubungkan seluruh dunia.</li>
      <li>PAN: Koneksi Bluetooth antara smartphone dan headset.</li>
    </ul>
  `
}
,
  {title:"Topologi Jaringan",ringkasan:"Mengenal bentuk topologi jaringan.",full:`
    <h3>Topologi Jaringan</h3>

    <p>Topologi jaringan adalah cara atau pola pengaturan fisik dan logis perangkat dalam suatu jaringan komputer. Topologi menentukan bagaimana data mengalir dan bagaimana perangkat terhubung satu sama lain.</p>

    <h4>1. Topologi Bus</h4>
    <p>Topologi Bus menggunakan satu kabel utama (backbone) yang menghubungkan semua perangkat. Data dikirim ke seluruh perangkat, tapi hanya perangkat tujuan yang memprosesnya.</p>
    <ul>
      <li><b>Kelebihan:</b> Hemat kabel, mudah diterapkan pada jaringan kecil.</li>
      <li><b>Kekurangan:</b> Sulit troubleshooting, jika kabel utama putus, jaringan terhenti.</li>
      <li><b>Contoh:</b> Jaringan kantor kecil atau laboratorium komputer.</li>
    </ul>

    <h4>2. Topologi Star</h4>
    <p>Topologi Star menggunakan perangkat pusat (hub atau switch). Semua perangkat terhubung ke pusat ini.</p>
    <ul>
      <li><b>Kelebihan:</b> Mudah manajemen, jika satu kabel putus tidak memengaruhi jaringan lain.</li>
      <li><b>Kekurangan:</b> Perangkat pusat menjadi single point of failure.</li>
      <li><b>Contoh:</b> LAN di sekolah atau kantor modern.</li>
    </ul>

    <h4>3. Topologi Ring</h4>
    <p>Topologi Ring menghubungkan perangkat membentuk lingkaran. Data mengalir dalam satu arah atau dua arah tergantung implementasi.</p>
    <ul>
      <li><b>Kelebihan:</b> Data bergerak teratur, cocok untuk jaringan jarak menengah.</li>
      <li><b>Kekurangan:</b> Jika satu perangkat gagal, bisa mengganggu jaringan.</li>
      <li><b>Contoh:</b> Beberapa jaringan kantor lama atau kampus.</li>
    </ul>

    <h4>4. Topologi Mesh</h4>
    <p>Topologi Mesh menghubungkan setiap perangkat dengan perangkat lainnya secara langsung.</p>
    <ul>
      <li><b>Kelebihan:</b> Sangat handal, jika satu jalur putus, data bisa lewat jalur lain.</li>
      <li><b>Kekurangan:</b> Biaya tinggi, banyak kabel, kompleks untuk besar jaringan.</li>
      <li><b>Contoh:</b> Internet backbone atau jaringan pusat data.</li>
    </ul>

    <h4>5. Topologi Hybrid</h4>
    <p>Topologi Hybrid adalah kombinasi dari dua atau lebih topologi dasar, misalnya Star + Bus.</p>
    <ul>
      <li><b>Kelebihan:</b> Fleksibel, dapat disesuaikan dengan kebutuhan jaringan.</li>
      <li><b>Kekurangan:</b> Struktur rumit, biaya lebih tinggi.</li>
      <li><b>Contoh:</b> Jaringan perusahaan besar atau kampus multi-gedung.</li>
    </ul>
  `
    
},
  {title:"Perangkat Jaringan",ringkasan:"Memahami router, switch, hub.",full:`
    <h3>Perangkat Jaringan</h3>

    <p>Perangkat jaringan adalah alat atau hardware yang digunakan untuk membangun dan mengelola jaringan komputer. Perangkat ini menentukan bagaimana data dapat dikirim, diterima, dan dikelola dalam jaringan.</p>

    <h4>1. Router</h4>
    <p>Router adalah perangkat yang menghubungkan beberapa jaringan dan mengarahkan paket data antar jaringan.</p>
    <ul>
      <li><b>Fungsi:</b> Menghubungkan LAN ke WAN, meneruskan data, membagi alamat IP (DHCP).</li>
      <li><b>Contoh:</b> Router Wi-Fi rumah, router kantor.</li>
      <li><b>Kelebihan:</b> Memungkinkan koneksi internet dan antar jaringan, mendukung firewall dasar.</li>
      <li><b>Kekurangan:</b> Biaya lebih tinggi dibanding hub/switch sederhana.</li>
    </ul>

    <h4>2. Switch</h4>
    <p>Switch adalah perangkat yang menghubungkan beberapa perangkat di jaringan LAN dan mengirimkan data hanya ke perangkat tujuan.</p>
    <ul>
      <li><b>Fungsi:</b> Mengelola lalu lintas data antar perangkat di LAN, lebih efisien dibanding hub.</li>
      <li><b>Contoh:</b> Switch di kantor atau laboratorium komputer.</li>
      <li><b>Kelebihan:</b> Efisien, mengurangi tabrakan data.</li>
      <li><b>Kekurangan:</b> Hanya bekerja di LAN, tidak menghubungkan ke jaringan lain.</li>
    </ul>

    <h4>3. Hub</h4>
    <p>Hub adalah perangkat lama yang menghubungkan beberapa komputer dalam LAN, tetapi data dikirim ke semua perangkat.</p>
    <ul>
      <li><b>Fungsi:</b> Menghubungkan perangkat di LAN.</li>
      <li><b>Contoh:</b> Hub 8-port untuk jaringan kecil.</li>
      <li><b>Kelebihan:</b> Murah dan mudah digunakan.</li>
      <li><b>Kekurangan:</b> Tidak efisien, semua perangkat menerima data, rawan tabrakan.</li>
    </ul>

    <h4>4. Access Point (AP)</h4>
    <p>Access Point memungkinkan perangkat wireless (Wi-Fi) terhubung ke jaringan LAN.</p>
    <ul>
      <li><b>Fungsi:</b> Memperluas jaringan LAN ke perangkat nirkabel.</li>
      <li><b>Contoh:</b> AP Wi-Fi di sekolah atau kantor.</li>
      <li><b>Kelebihan:</b> Mendukung mobilitas pengguna, memperluas jangkauan jaringan.</li>
      <li><b>Kekurangan:</b> Biaya tambahan, sinyal tergantung lokasi.</li>
    </ul>

    <h4>5. Kabel Jaringan</h4>
    <p>Kabel jaringan adalah media fisik yang digunakan untuk menghubungkan perangkat di jaringan.</p>
    <ul>
      <li><b>Jenis Kabel:</b> UTP (Unshielded Twisted Pair), STP (Shielded Twisted Pair), Fiber Optik.</li>
      <li><b>Fungsi:</b> Menyalurkan data antara perangkat.</li>
      <li><b>Kelebihan:</b> Stabil, cepat, terutama kabel fiber optic untuk jarak jauh.</li>
      <li><b>Kekurangan:</b> Kabel panjang sulit dikelola, fiber optic mahal.</li>
    </ul>
  `
},
  {title:"Pengkabelan & Crimping",ringkasan:"Belajar kabel UTP, RJ45, crimping.",full:`
    <h3>Pengkabelan & Crimping</h3>

    <p>Pengkabelan adalah proses menghubungkan perangkat jaringan menggunakan kabel, sedangkan crimping adalah teknik memasang konektor RJ45 pada kabel UTP agar siap digunakan di jaringan LAN.</p>

    <h4>1. Kabel UTP (Unshielded Twisted Pair)</h4>
    <p>Kabel UTP sering digunakan untuk jaringan LAN. Fleksibel, mudah dipasang, dan tersedia dalam beberapa kategori (Cat5e, Cat6).</p>
    <img src="images/kabel.webp" class="materi-img" alt="Kabel UTP">

    <h4>2. Konektor RJ45 & Tang Crimping</h4>
    <p>Gunakan konektor RJ45 untuk menghubungkan kabel ke perangkat. Tang crimping digunakan untuk menekan kabel ke konektor.</p>
    <div style="display:flex;gap:10px;">
      <img src="images/konektor.jpg" class="materi-images-small" alt="Konektor RJ45">
      <img src="images/tang crimping.png" class="materi-images-small" alt="Tang Crimping">
      <img src="images/router.jpg" class="materi-images-small" alt="Router">
    </div>

    <h4>3. Standar Susunan Kabel</h4>
    <p>Terdapat dua standar susunan kabel UTP ke RJ45: <b>T568A</b> dan <b>T568B</b>. Berikut tabel warna agar lebih mudah dipahami:</p>

    <h5>T568A</h5>
    <table border="1" cellpadding="5" cellspacing="0">
      <tr>
        <th>Pin</th>
        <th>Warna Kabel</th>
        <th>Visual</th>
      </tr>
      <tr><td>1</td><td>Putih/Hijau</td><td style="background:#00FF00;"></td></tr>
      <tr><td>2</td><td>Hijau</td><td style="background:#008000;"></td></tr>
      <tr><td>3</td><td>Putih/Oranye</td><td style="background:#FFA500;"></td></tr>
      <tr><td>4</td><td>Biru</td><td style="background:#0000FF;"></td></tr>
      <tr><td>5</td><td>Putih/Biru</td><td style="background:#ADD8E6;"></td></tr>
      <tr><td>6</td><td>Oranye</td><td style="background:#FF8C00;"></td></tr>
      <tr><td>7</td><td>Putih/Coklat</td><td style="background:#D2B48C;"></td></tr>
      <tr><td>8</td><td>Coklat</td><td style="background:#8B4513;"></td></tr>
    </table>

    <h5>T568B</h5>
    <table border="1" cellpadding="5" cellspacing="0">
      <tr>
        <th>Pin</th>
        <th>Warna Kabel</th>
        <th>Visual</th>
      </tr>
      <tr><td>1</td><td>Putih/Oranye</td><td style="background:#FFA500;"></td></tr>
      <tr><td>2</td><td>Oranye</td><td style="background:#FF8C00;"></td></tr>
      <tr><td>3</td><td>Putih/Hijau</td><td style="background:#00FF00;"></td></tr>
      <tr><td>4</td><td>Biru</td><td style="background:#0000FF;"></td></tr>
      <tr><td>5</td><td>Putih/Biru</td><td style="background:#ADD8E6;"></td></tr>
      <tr><td>6</td><td>Hijau</td><td style="background:#008000;"></td></tr>
      <tr><td>7</td><td>Putih/Coklat</td><td style="background:#D2B48C;"></td></tr>
      <tr><td>8</td><td>Coklat</td><td style="background:#8B4513;"></td></tr>
    </table>

    <h4>4. Teknik Crimping</h4>
    <ol>
      <li>Siapkan kabel UTP sesuai panjang.</li>
      <li>Kupas kulit kabel ±2-3 cm.</li>
      <li>Susun kabel sesuai T568A atau T568B.</li>
      <li>Potong rata ujung kabel.</li>
      <li>Masukkan kabel ke konektor RJ45 hingga terdengar “klik”.</li>
      <li>Tekan konektor menggunakan tang crimping.</li>
      <li>Uji kabel dengan tester untuk memastikan koneksi benar.</li>
    </ol>

    <h4>5. Tips Praktik</h4>
    <ul>
      <li>Jangan longgarkan kabel di konektor RJ45.</li>
      <li>Gunakan kabel sesuai kategori (Cat5e/Cat6).</li>
      <li>Hindari menekuk kabel terlalu tajam.</li>
    </ul>

    <p>Dengan pengkabelan dan crimping yang tepat, jaringan LAN akan berfungsi optimal, siap untuk sharing data, printer, atau akses internet.</p>
  `
},
  {title:"IP Address & Subnet",ringkasan:"Memahami IP dan subnetting.",full:`
    <h3>IP Address & Subnet</h3>

    <p>IP Address adalah alamat unik yang diberikan ke setiap perangkat dalam jaringan agar dapat saling berkomunikasi. Subnet digunakan untuk membagi jaringan menjadi beberapa bagian agar lebih efisien dan aman.</p>

    <h4>1. Pengertian IP Address</h4>
    <ul>
      <li>IP Address terdiri dari 32 bit (IPv4) atau 128 bit (IPv6), biasanya ditulis dalam format desimal (IPv4) seperti <b>192.168.1.1</b>.</li>
      <li>Fungsi utama IP Address: Identifikasi perangkat dan lokasi jaringan.</li>
    </ul>

    <h4>2. Kelas IP Address (IPv4)</h4>
    <p>IP dibagi menjadi beberapa kelas:</p>
    <table border="1" cellpadding="5" cellspacing="0">
      <tr>
        <th>Kelas</th>
        <th>Range IP</th>
        <th>Default Subnet Mask</th>
        <th>Keterangan</th>
      </tr>
      <tr><td>A</td><td>1.0.0.1 - 126.255.255.254</td><td>255.0.0.0</td><td>Jaringan besar, organisasi besar</td></tr>
      <tr><td>B</td><td>128.0.0.1 - 191.255.255.254</td><td>255.255.0.0</td><td>Jaringan menengah, kampus atau perusahaan</td></tr>
      <tr><td>C</td><td>192.0.0.1 - 223.255.255.254</td><td>255.255.255.0</td><td>Jaringan kecil, LAN kantor atau rumah</td></tr>
      <tr><td>D</td><td>224.0.0.0 - 239.255.255.255</td><td>-</td><td>Multicast</td></tr>
      <tr><td>E</td><td>240.0.0.0 - 254.255.255.254</td><td>-</td><td>Eksperimen/Reserved</td></tr>
    </table>

    <h4>3. Subnet Mask</h4>
    <p>Subnet mask menentukan bagian jaringan (network) dan bagian host di IP Address.</p>
    <ul>
      <li>Contoh: IP 192.168.1.10/24 → Network: 192.168.1.0, Host: 10</li>
      <li>/24 artinya subnet mask 255.255.255.0 → 256 alamat IP total, 254 host usable</li>
    </ul>

    <h4>4. Contoh Perhitungan Subnet</h4>
    <p>Misalkan kita memiliki IP 192.168.10.0/26:</p>
    <ul>
      <li>Subnet mask: 255.255.255.192</li>
      <li>Total host: 2^(32-26) - 2 = 62 host usable</li>
      <li>Range host: 192.168.10.1 - 192.168.10.62</li>
      <li>Broadcast: 192.168.10.63</li>
    </ul>

    <p>Dengan subnetting, kita bisa membagi jaringan besar menjadi beberapa jaringan kecil untuk meningkatkan keamanan dan efisiensi penggunaan IP.</p>

    <h4>5. Tips Praktik</h4>
    <ul>
      <li>Gunakan IP private (192.168.x.x, 10.x.x.x, 172.16.x.x) untuk jaringan internal.</li>
      <li>Jangan gunakan IP publik sembarangan karena bisa konflik di internet.</li>
      <li>Gunakan kalkulator subnet online untuk menghitung jaringan kompleks.</li>
    </ul>
  `
},
  {title:"Sharing Printer & File",ringkasan:"Belajar berbagi printer dan file.",full:`
<h3>Sharing Printer & Transfer File</h3>

    <p>Sharing printer dan transfer file memungkinkan perangkat di jaringan lokal (LAN) untuk saling menggunakan sumber daya tanpa perlu kabel tambahan atau perangkat terpisah. Ini penting di laboratorium, kantor, atau sekolah.</p>

    <h4>1. Sharing Printer</h4>
    <p>Dengan sharing printer, satu printer bisa digunakan oleh beberapa komputer dalam jaringan.</p>

    <h5>Langkah Setting di Windows:</h5>
    <ol>
      <li>Hubungkan printer ke salah satu komputer (host) dan pastikan printer dapat digunakan.</li>
      <li>Buka <b>Control Panel → Devices and Printers</b>.</li>
      <li>Klik kanan printer → <b>Printer properties → Sharing → Share this printer</b>.</li>
      <li>Catat nama printer yang di-share.</li>
      <li>Di komputer lain, buka <b>Control Panel → Devices and Printers → Add Printer → Network Printer</b> dan pilih printer yang di-share.</li>
    </ol>

    <h5>Tips:</h5>
    <ul>
      <li>Gunakan IP statis agar printer selalu mudah diakses.</li>
      <li>Pastikan firewall tidak memblokir jaringan printer.</li>
    </ul>

    <h4>2. Transfer File Antar Komputer</h4>
    <p>Transfer file memungkinkan berbagi dokumen, gambar, dan data antar komputer di jaringan LAN.</p>

    <h5>Metode Umum:</h5>
    <ol>
      <li><b>Folder Sharing (Windows):</b> Pilih folder → klik kanan → Properties → Sharing → Share → Pilih user.</li>
      <li><b>Network Drive Mapping:</b> Map folder yang di-share sebagai drive di komputer lain untuk akses cepat.</li>
      <li><b>FTP Server:</b> Gunakan File Transfer Protocol untuk transfer file antar perangkat.</li>
      <li><b>Software Tambahan:</b> Misal menggunakan aplikasi Sync, TeamViewer, atau cloud lokal seperti Nextcloud.</li>
    </ol>

    <h5>Tips:</h5>
    <ul>
      <li>Gunakan nama folder yang jelas agar mudah dikenali.</li>
      <li>Atur permission agar user tertentu saja yang dapat mengakses file penting.</li>
      <li>Gunakan IP statis untuk memudahkan akses folder.</li>
    </ul>
  `},
  {title:"Protokol & Internet",ringkasan:"Mengenal TCP/IP, HTTP, FTP.",full:`
    <h3>Protokol & Internet</h3>

    <p>Protokol adalah aturan atau standar komunikasi antar perangkat jaringan agar dapat saling mengirim dan menerima data dengan benar. Internet merupakan jaringan global yang menghubungkan jutaan perangkat menggunakan protokol ini.</p>

    <h4>1. Pengertian Protokol</h4>
    <ul>
      <li>Protokol menentukan format, urutan, dan aturan pertukaran data.</li>
      <li>Tanpa protokol, perangkat tidak bisa memahami data yang diterima.</li>
    </ul>

    <h4>2. Jenis-Jenis Protokol Penting</h4>
    <table border="1" cellpadding="5" cellspacing="0">
      <tr>
        <th>Protokol</th>
        <th>Fungsi</th>
      </tr>
      <tr><td>TCP (Transmission Control Protocol)</td><td>Menjamin data terkirim dengan benar, koneksi reliable.</td></tr>
      <tr><td>IP (Internet Protocol)</td><td>Memberikan alamat IP dan mengatur pengiriman paket data.</td></tr>
      <tr><td>HTTP / HTTPS</td><td>Protokol komunikasi untuk mengakses website.</td></tr>
      <tr><td>FTP (File Transfer Protocol)</td><td>Untuk transfer file antar perangkat atau server.</td></tr>
      <tr><td>DNS (Domain Name System)</td><td>Menerjemahkan nama domain menjadi alamat IP.</td></tr>
      <tr><td>DHCP (Dynamic Host Configuration Protocol)</td><td>Memberikan IP otomatis ke perangkat di jaringan.</td></tr>
      <tr><td>SMTP / POP3 / IMAP</td><td>Protokol pengiriman dan penerimaan email.</td></tr>
    </table>

    <h4>3. Cara Kerja Internet</h4>
    <ol>
      <li>Perangkat mengirim permintaan data (misal akses website).</li>
      <li>Permintaan dikirim melalui protokol TCP/IP ke server tujuan.</li>
      <li>Server mengirim data kembali sesuai permintaan.</li>
      <li>Router, switch, dan ISP memastikan data sampai ke perangkat pengirim.</li>
    </ol>

    <h4>4. Gambar Ilustrasi</h4>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <img src="img/protokol_tcpip.jpg" class="materi-img-small" alt="Protokol TCP/IP">
      <img src="img/internet_works.jpg" class="materi-img-small" alt="Cara Kerja Internet">
      <img src="img/dns_server.jpg" class="materi-img-small" alt="DNS Server">
    </div>

    <h4>5. Tips Praktik</h4>
    <ul>
      <li>Pahami protokol dasar seperti TCP/IP, HTTP, dan FTP agar mudah troubleshooting jaringan.</li>
      <li>Gunakan DNS terpercaya agar akses internet stabil.</li>
      <li>Amankan koneksi dengan HTTPS dan firewall.</li>
    </ul>

    <p>Dengan memahami protokol dan internet, siswa dapat merancang jaringan lebih aman, mengatur koneksi data, dan memahami bagaimana perangkat berkomunikasi di jaringan lokal maupun global.</p>
  `},
  {title:"Keamanan Jaringan",ringkasan:"Firewall, antivirus, password.",full:`
    <h3>Keamanan Jaringan</h3>

    <p>Keamanan jaringan adalah upaya untuk melindungi data, perangkat, dan layanan jaringan dari ancaman internal maupun eksternal, sehingga jaringan tetap aman dan berjalan optimal.</p>

    <h4>1. Firewall</h4>
    <p>Firewall berfungsi sebagai penghalang antara jaringan internal dan jaringan luar (internet). Firewall dapat:</p>
    <ul>
      <li>Memblokir akses tidak sah</li>
      <li>Mengontrol lalu lintas data masuk dan keluar</li>
      <li>Melindungi jaringan dari serangan cyber</li>
    </ul>
    <img src="img/firewall.jpg" class="materi-img" alt="Firewall">

    <h4>2. Antivirus / Antimalware</h4>
    <p>Program antivirus dan antimalware melindungi perangkat dari virus, spyware, trojan, dan malware lainnya. Penting untuk selalu update database antivirus agar tetap efektif.</p>
    <img src="img/antivirus.jpg" class="materi-img" alt="Antivirus">

    <h4>3. Kontrol Akses</h4>
    <p>Memberikan izin akses hanya untuk pengguna tertentu:</p>
    <ul>
      <li>Username & Password</li>
      <li>IP Filtering</li>
      <li>VPN untuk akses remote yang aman</li>
    </ul>

    <h4>4. Enkripsi</h4>
    <p>Enkripsi digunakan untuk mengamankan data agar tidak bisa dibaca oleh pihak lain saat dikirim melalui jaringan. Contohnya:</p>
    <ul>
      <li>WPA2/WPA3 pada jaringan Wi-Fi</li>
      <li>HTTPS untuk akses website aman</li>
      <li>VPN untuk koneksi terenkripsi</li>
    </ul>

    <h4>5. Update Software</h4>
    <p>Selalu perbarui sistem operasi, aplikasi, dan perangkat jaringan agar terhindar dari celah keamanan yang bisa dimanfaatkan hacker.</p>

    <h4>6. Tips Praktik</h4>
    <ul>
      <li>Gunakan password yang kuat dan rutin diganti.</li>
      <li>Lakukan backup data penting secara berkala.</li>
      <li>Gunakan firewall dan antivirus yang terpercaya.</li>
      <li>Catat konfigurasi jaringan untuk mempermudah manajemen.</li>
    </ul>
    `},

  {title:"Troubleshooting",ringkasan:"Cara mendiagnosis masalah jaringan.",full:`
     <h3>Troubleshooting Jaringan</h3>

    <p>Troubleshooting jaringan adalah proses menemukan dan memperbaiki masalah pada jaringan sehingga perangkat dapat berkomunikasi dengan lancar.</p>

    <h4>1. Identifikasi Masalah</h4>
    <p>Langkah awal troubleshooting adalah mengetahui gejala masalah:</p>
    <ul>
      <li>Komputer tidak terkoneksi ke jaringan</li>
      <li>Internet lambat atau putus-putus</li>
      <li>Printer atau file sharing tidak dapat diakses</li>
      <li>Alamat IP konflik atau salah konfigurasi</li>
    </ul>

    <h4>2. Periksa Hardware & Kabel</h4>
    <ul>
      <li>Pastikan kabel LAN, switch, router, dan konektor terpasang dengan baik</li>
      <li>Ganti kabel jika rusak atau longgar</li>
      <li>Periksa lampu indikator pada perangkat jaringan</li>
    </ul>

    <h4>3. Gunakan Perintah Dasar</h4>
    <ul>
      <li><b>ping</b> → cek konektivitas ke host atau IP tertentu</li>
      <li><b>ipconfig / ifconfig</b> → cek IP dan konfigurasi jaringan</li>
      <li><b>tracert / traceroute</b> → cek jalur paket data</li>
      <li><b>nslookup</b> → cek DNS server dan resolusi nama domain</li>
      <li><b>netstat</b> → cek koneksi aktif pada komputer</li>
    </ul>

    <h4>4. Periksa Software & Firewall</h4>
    <ul>
      <li>Pastikan antivirus atau firewall tidak memblokir koneksi jaringan</li>
      <li>Nonaktifkan sementara firewall jika perlu uji koneksi</li>
      <li>Periksa konfigurasi IP, gateway, dan DNS</li>
    </ul>

    <h4>5. Restart Perangkat</h4>
    <ul>
      <li>Restart komputer atau perangkat jaringan (router, switch)</li>
      <li>Reset konfigurasi sementara jika diperlukan</li>
    </ul>
    <img src="img/network_restart.jpg" class="materi-img" alt="Restart Perangkat Jaringan">
  `},
];

// ==== LOGIN & BERANDA ====
// ... (tetap sama, tidak diubah)

// ==== MATERI INTERAKTIF ====
// Data materi tetap sama

// Render sidebar materi
const materiList = document.getElementById('materiList'); // Sidebar
const materiContainer = document.getElementById('materi-list'); // Ringkasan di content
const materiFullContent = document.getElementById('materi-full-content');
const popup = document.getElementById('materi-full-popup');
const closePopup = document.getElementById('close-popup');
let currentIndex = 0;

// Sidebar
if (materiList) {
  materiData.forEach((m, index) => {
    const li = document.createElement('li');
    li.textContent = m.title;
    li.className = 'sidebar-item';
    li.addEventListener('click', () => {
      currentIndex = index;
      scrollToRingkasan(index);
    });
    materiList.appendChild(li);
  });
}

// Render ringkasan materi di content
if (materiContainer) {
  materiData.forEach((m, index) => {
    const div = document.createElement('div');
    div.className = 'materi-item';
    div.innerHTML = `
      <h3>${m.title}</h3>
      <p>${m.ringkasan}</p>
      <button class="btn-full" data-index="${index}">Lihat Selengkapnya</button>
    `;
    materiContainer.appendChild(div);
  });
}

// Fungsi scroll ke ringkasan saat sidebar diklik
function scrollToRingkasan(index) {
  const items = document.querySelectorAll('.materi-item');
  if(items[index]){
    items[index].scrollIntoView({behavior:'smooth', block:'start'});
  }
}

// Event popup materi full
materiContainer.addEventListener('click', (e) => {
  if (e.target.classList.contains('btn-full')) {
    const index = e.target.dataset.index;
    materiFullContent.innerHTML = materiData[index].full;
    popup.style.display = 'flex';
  }
});

closePopup.addEventListener('click', () => popup.style.display = 'none');
window.addEventListener('click', (e) => {
  if (e.target === popup) popup.style.display = 'none';
});

// Tombol navigasi (prev/next)
document.getElementById('prevBtn')?.addEventListener('click', () => {
  if (currentIndex > 0) { currentIndex--; scrollToRingkasan(currentIndex); }
});
document.getElementById('nextBtn')?.addEventListener('click', () => {
  if (currentIndex < materiData.length - 1) { currentIndex++; scrollToRingkasan(currentIndex); }
});

// Inisialisasi ringkasan pertama
scrollToRingkasan(0);

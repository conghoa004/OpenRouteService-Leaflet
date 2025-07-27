<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <title>Tìm kỹ thuật viên gần nhất</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Thư viện bản đồ Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map {
            width: 100%;
            height: 600px;
        }

        #info {
            margin-top: 10px;
            font-size: 16px;
            font-weight: bold;
            white-space: pre-line;
        }

        #addressInput {
            width: 60%;
            padding: 8px;
            margin-bottom: 10px;
            font-size: 16px;
        }

        #searchBtn {
            padding: 8px 12px;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <h2>Nhập địa chỉ khách hàng để tìm kỹ thuật viên gần nhất</h2>

    <!-- Nhập địa chỉ -->
    <input type="text" id="addressInput" placeholder="VD: 1 Nguyễn Văn Bảo, Gò Vấp, TP.HCM" />
    <button id="searchBtn" onclick="geocodeAddress()">Tìm vị trí</button>
    <br />
    <h3>Hoặc bấm vào bản đồ để chọn vị trí khách hàng</h3>

    <!-- Bản đồ -->
    <div id="map"></div>

    <!-- Thông tin kết quả -->
    <div id="info">Vui lòng nhập địa chỉ khách hàng ở ô trên hoặc bấm vào bản đồ.</div>

    <!-- Thư viện Leaflet -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // Khóa API OpenRouteService (thay bằng khóa thực tế nếu không dùng Laravel Blade)
        const apiKey = "{{ env('OPEN_ROUTE_SERVICE_API_KEY') }}";

        // Tạo bản đồ tại TP.HCM
        const map = L.map("map").setView([10.762622, 106.660172], 13);

        // Lớp nền OSM
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors"
        }).addTo(map);

        const infoDiv = document.getElementById("info");

        // Danh sách kỹ thuật viên
        const technicians = [
            { id: 1, name: "Nguyễn Văn A", lat: 10.767, lng: 106.682 },
            { id: 2, name: "Trần Văn B", lat: 10.758, lng: 106.669 },
            { id: 3, name: "Lê Văn C", lat: 10.751, lng: 106.675 },
            { id: 4, name: "Phạm Văn D", lat: 10.776, lng: 106.700 },
            { id: 5, name: "Võ Thị E", lat: 10.785, lng: 106.654 },
            { id: 6, name: "Đỗ Minh F", lat: 10.762, lng: 106.629 },
            { id: 7, name: "Lý Văn G", lat: 10.732, lng: 106.660 },
            { id: 8, name: "Trịnh Văn H", lat: 10.827, lng: 106.700 },
            { id: 9, name: "Huỳnh T. I", lat: 10.748, lng: 106.666 },
            { id: 10, name: "Ngô Văn K", lat: 10.795, lng: 106.683 },
            { id: 11, name: "Bùi Văn L", lat: 10.851, lng: 106.755 },
            { id: 12, name: "Trần T. M", lat: 10.980, lng: 106.653 },
            { id: 13, name: "Vũ T. N", lat: 10.541, lng: 105.695 },
            { id: 14, name: "Nguyễn T. O", lat: 10.045, lng: 105.746 },
            { id: 15, name: "Phan Văn P", lat: 11.003, lng: 106.660 },
            { id: 16, name: "Hoàng Văn Q", lat: 10.585, lng: 106.398 },
            { id: 17, name: "Trịnh Thị R", lat: 10.920, lng: 106.822 },
            { id: 18, name: "Mai Văn S", lat: 10.620, lng: 106.510 },
            { id: 19, name: "Đặng Văn T", lat: 10.373, lng: 106.360 },
            { id: 20, name: "Nguyễn Văn U", lat: 10.870, lng: 106.810 }
        ];

        // Vẽ các kỹ thuật viên lên bản đồ
        technicians.forEach(t => {
            L.marker([t.lat, t.lng], {
                icon: L.icon({
                    iconUrl: "https://cdn-icons-png.flaticon.com/512/447/447031.png",
                    iconSize: [25, 25]
                })
            }).addTo(map).bindPopup(`${t.name}`);
        });

        // Biến lưu marker/tuyến đường cũ để xóa
        let customerMarker = null;
        let routeLine = null;

        // Hàm chuyển địa chỉ sang tọa độ
        async function geocodeAddress() {
            const address = document.getElementById("addressInput").value.trim();
            if (!address) {
                alert("Vui lòng nhập địa chỉ khách hàng");
                return;
            }

            const url = `https://api.openrouteservice.org/geocode/search?api_key=${apiKey}&text=${encodeURIComponent(address)}&boundary.country=VN&size=3`;

            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error("Lỗi geocoding");

                const data = await response.json();
                const result = data.features.find(f => f.properties.confidence >= 0.8);
                if (!result) throw new Error("Không tìm thấy địa chỉ chính xác");

                const [lng, lat] = result.geometry.coordinates;

                // Gán vị trí khách hàng
                if (customerMarker) map.removeLayer(customerMarker);
                customerMarker = L.marker([lat, lng]).addTo(map).bindPopup("Khách hàng").openPopup();
                map.setView([lat, lng], 14);

                infoDiv.textContent = "Đang tìm kỹ thuật viên gần nhất...";
                await findNearestTechnician({ lat, lng });

            } catch (err) {
                console.error("Lỗi geocode:", err);
                infoDiv.textContent = "Không tìm thấy địa chỉ phù hợp.";
            }
        }

        // Hàm tính quãng đường lái xe
        async function getDrivingDistance(start, end) {
            const url = "https://api.openrouteservice.org/v2/directions/driving-car/geojson";
            const body = {
                coordinates: [
                    [start.lng, start.lat],
                    [end.lng, end.lat]
                ]
            };

            const response = await fetch(url, {
                method: "POST",
                headers: {
                    "Authorization": apiKey,
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(body)
            });

            if (!response.ok) {
                const msg = await response.text();
                throw new Error(`ORS lỗi: ${response.status} - ${msg}`);
            }

            const data = await response.json();
            const route = data.features[0];

            return {
                coords: route.geometry.coordinates.map(c => [c[1], c[0]]), // [lng, lat] => [lat, lng]
                distance: route.properties.summary.distance,
                duration: route.properties.summary.duration
            };
        }

        // Tìm kỹ thuật viên gần nhất theo khoảng cách lái xe
        async function findNearestTechnician(customer) {
            let nearest = null;
            let shortest = Infinity;
            let bestRoute = null;

            for (const tech of technicians) {
                try {
                    const result = await getDrivingDistance({
                        lat: tech.lat,
                        lng: tech.lng
                    }, customer);

                    if (result.distance < shortest) {
                        shortest = result.distance;
                        nearest = tech;
                        bestRoute = result;
                    }
                } catch (err) {
                    console.warn(`Lỗi khi tính cho ${tech.name}:`, err.message);
                }
            }

            if (nearest && bestRoute) {
                if (routeLine) map.removeLayer(routeLine);

                // Vẽ tuyến đường
                routeLine = L.polyline(bestRoute.coords, {
                    color: "blue",
                    weight: 4
                }).addTo(map);

                map.fitBounds(routeLine.getBounds(), { padding: [40, 40], maxZoom: 15 });

                infoDiv.textContent =
                    `Khách hàng: ${customer.lat.toFixed(5)}, ${customer.lng.toFixed(5)}\n` +
                    `Gần nhất: ${nearest.name}\n` +
                    `Khoảng cách: ${(bestRoute.distance / 1000).toFixed(2)} km\n` +
                    `Ước tính: ${Math.round(bestRoute.duration / 60)} phút`;
            } else {
                infoDiv.textContent = "Không tìm được kỹ thuật viên phù hợp.";
            }
        }

        // Người dùng bấm vào bản đồ để chọn vị trí
        map.on('click', async function (e) {
            const { lat, lng } = e.latlng;

            if (customerMarker) map.removeLayer(customerMarker);
            customerMarker = L.marker([lat, lng]).addTo(map).bindPopup("Khách hàng").openPopup();

            infoDiv.textContent = "Đang tìm kỹ thuật viên gần nhất...";
            await findNearestTechnician({ lat, lng });
        });
    </script>
</body>

</html>
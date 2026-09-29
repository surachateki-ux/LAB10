import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

void main() {
  runApp(const SmartFarmApp());
}

class SmartFarmApp extends StatelessWidget {
  const SmartFarmApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Smart Farm Dashboard',
      theme: ThemeData(primarySwatch: Colors.green),
      home: const DashboardScreen(),
    );
  }
}

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  // ปรับ IP ให้ตรงกับเครื่องของคุณ (เช่น 10.96.47.121 หรือ 10.0.2.2 สำหรับ Android Emulator)
  // สำหรับ Android Emulator
  final String apiUrl = 'http://10.0.2.2/smart_farm/api/get_latest.php';

  double temp = 0.0;
  double humid = 0.0;
  String fruitName = '-';
  int quantity = 0;
  int latencyMs = 0;
  bool isLoading = false;

  Future<void> fetchData() async {
    setState(() => isLoading = true);
    final stopwatch = Stopwatch()..start();

    try {
      final response = await http.get(Uri.parse(apiUrl));
      stopwatch.stop();

      if (response.statusCode == 200) {
        final jsonResponse = json.decode(response.body);
        final data = jsonResponse['data'];

        setState(() {
          temp = (data['telemetry']['temperature'] as num).toDouble();
          humid = (data['telemetry']['humidity'] as num).toDouble();
          fruitName = data['inventory']['fruit_name'];
          quantity = data['inventory']['quantity'];
          latencyMs = stopwatch.elapsedMilliseconds;
        });
      }
    } catch (e) {
      debugPrint("Error fetching data: $e");
    } finally {
      setState(() => isLoading = false);
    }
  }

  @override
  void initState() {
    super.initState();
    fetchData();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Smart Agriculture Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: fetchData,
          )
        ],
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // เซนเซอร์อุณหภูมิ/ความชื้น
                  Card(
                    color: Colors.orange.shade50,
                    child: Padding(
                      padding: const EdgeInsets.all(16.0),
                      child: Column(
                        children: [
                          const Text('สภาพแวดล้อม (Telemetry)',
                              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                          const SizedBox(height: 10),
                          Text('อุณหภูมิ: $temp °C', style: const TextStyle(fontSize: 16)),
                          Text('ความชื้น: $humid %', style: const TextStyle(fontSize: 16)),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  // จำนวนผลไม้ AI
                  Card(
                    color: Colors.green.shade50,
                    child: Padding(
                      padding: const EdgeInsets.all(16.0),
                      child: Column(
                        children: [
                          const Text('คลังสินค้า AI (Inventory)',
                              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                          const SizedBox(height: 10),
                          Text('ชนิดผลไม้: $fruitName', style: const TextStyle(fontSize: 16)),
                          Text('จำนวนที่ตรวจจับได้: $quantity ชิ้น',
                              style: const TextStyle(fontSize: 16)),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),
                  // แสดง Latency
                  Text(
                    'Response Latency: $latencyMs ms',
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: Colors.grey, fontWeight: FontWeight.bold),
                  ),
                ],
              ),
            ),
    );
  }
}
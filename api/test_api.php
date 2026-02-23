<!DOCTYPE html>
<html>
<head>
    <title>Test Arduino API</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .test-btn { background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        .result { margin-top: 20px; padding: 10px; border: 1px solid #ddd; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>Arduino API Test</h1>
    <button class="test-btn" onclick="testAPI()">Test POST Request</button>
    <div id="result" class="result"></div>

    <script>
        async function testAPI() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = 'Testing...';
            
            try {
                const response = await fetch('recycling_data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        weight: 15.5,
                        distance: 8.2,
                        is_metal: false,
                        accepted: true,
                        device_id: 'TEST_001',
                        timestamp: Date.now()
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `
                        <h3>✅ SUCCESS!</h3>
                        <p><strong>Message:</strong> ${data.message}</p>
                        <p><strong>Tokens Earned:</strong> ${data.tokens_earned}</p>
                        <p>Your Arduino API is working perfectly!</p>
                    `;
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `
                        <h3>❌ Error</h3>
                        <p><strong>Error:</strong> ${data.error}</p>
                    `;
                }
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `
                    <h3>❌ Network Error</h3>
                    <p><strong>Error:</strong> ${error.message}</p>
                `;
            }
        }
    </script>
</body>
</html>

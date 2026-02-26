async function testApi() {
    try {
        const loginRes = await fetch('http://localhost/academia/backend/api/auth/login', {
            method: 'POST',
            body: JSON.stringify({
                email: 'alexander.mondocorre@gmail.com',
                password: 'password123'
            }),
            headers: { 'Content-Type': 'application/json' }
        });
        const loginData = await loginRes.json();
        console.log('Login:', loginData.token ? 'success' : loginData);

        const token = loginData.token;
        if (!token) return;

        const reportRes = await fetch('http://localhost/academia/backend/api/report/weekly?offset=0', {
            headers: { Authorization: `Bearer ${token}` }
        });
        const reportData = await reportRes.json();
        console.log(JSON.stringify(reportData, null, 2));

    } catch (error) {
        console.error(error.message);
    }
}
testApi();

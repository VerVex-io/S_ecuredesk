# Load testing (JMeter)

Add `securedesk.jmx` here with this scenario:
1. Login (POST /api/login), keeping the session cookie (HTTP Cookie Manager)
2. List tickets (GET /api/tickets)
3. Create ticket (POST /api/tickets)

Load levels: 50 / 100 / 200 concurrent users, 60 s ramp-up.

Run headless and generate the HTML report:

    jmeter -n -t securedesk.jmx -l results.jtl -e -o report/

Record in `RESULTS.md`: average / p95 response time, throughput, error rate,
the bottleneck you found (e.g. missing index, N+1 query), the fix, and the re-test.

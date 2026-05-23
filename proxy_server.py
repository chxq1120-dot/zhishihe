import http.server
import urllib.request
import urllib.error
import os
import json
import subprocess
import threading
import time

H5_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "frontend", "h5")
PHP_PORT = 8081
PHP_HOST = "127.0.0.1"

def start_php_server():
    time.sleep(1)
    proc = subprocess.Popen(
        ["php", "-S", f"{PHP_HOST}:{PHP_PORT}", "-t", "/workspace/public", "/workspace/public/router.php"],
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
        text=True,
        bufsize=1,
        universal_newlines=True
    )
    print(f"PHP backend started at http://{PHP_HOST}:{PHP_PORT}")
    # 读取 PHP 服务器的输出并打印
    for line in proc.stdout:
        print(line, end='')
    proc.wait()

class LocalHandler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=H5_DIR, **kwargs)

    def do_GET(self):
        if self.path.startswith("/api/"):
            self._forward_to_php("GET")
        else:
            super().do_GET()

    def do_POST(self):
        if self.path.startswith("/api/"):
            self._forward_to_php("POST")
        else:
            super().do_POST()

    def do_OPTIONS(self):
        self.send_response(204)
        self._set_cors_headers()
        self.end_headers()

    def _forward_to_php(self, method):
        try:
            url = f"http://{PHP_HOST}:{PHP_PORT}{self.path}"
            headers = {}
            for key, value in self.headers.items():
                if key.lower() not in ("host", "origin", "referer"):
                    headers[key] = value
            body = None
            if method == "POST":
                content_length = int(self.headers.get("Content-Length", 0))
                if content_length > 0:
                    body = self.rfile.read(content_length)
            req = urllib.request.Request(url, data=body, headers=headers, method=method)
            proxy_handler = urllib.request.ProxyHandler({})
            opener = urllib.request.build_opener(proxy_handler)
            with opener.open(req, timeout=15) as resp:
                resp_body = resp.read()
                self.send_response(resp.status)
                for key, value in resp.getheaders():
                    if key.lower() not in ("transfer-encoding", "connection"):
                        self.send_header(key, value)
                self._set_cors_headers()
                self.end_headers()
                self.wfile.write(resp_body)
        except urllib.error.HTTPError as e:
            resp_body = e.read()
            self.send_response(e.code)
            self._set_cors_headers()
            self.send_header("Content-Type", "application/json")
            self.end_headers()
            self.wfile.write(resp_body)
        except Exception as e:
            self.send_response(502)
            self._set_cors_headers()
            self.send_header("Content-Type", "application/json")
            self.end_headers()
            self.wfile.write(json.dumps({"code": 502, "msg": f"Backend error: {str(e)}"}).encode())

    def _set_cors_headers(self):
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type, Authorization, token")

if __name__ == "__main__":
    threading.Thread(target=start_php_server, daemon=True).start()
    port = 8080
    server = http.server.HTTPServer(("0.0.0.0", port), LocalHandler)
    print(f"Local server running at http://localhost:{port}")
    print(f"H5 static files from: {H5_DIR}")
    print(f"API requests forwarded to local PHP backend at http://{PHP_HOST}:{PHP_PORT}")
    server.serve_forever()
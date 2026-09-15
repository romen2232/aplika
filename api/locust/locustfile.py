from locust import HttpUser, task, between, events
import random
import string
import time

class PublicApiUser(HttpUser):
    """Simulates unauthenticated users hitting public endpoints"""
    wait_time = between(0.05, 0.2)
    weight = 3
    
    @task
    def hit_public_endpoint(self):
        """Hit a public endpoint without authentication"""
        with self.client.get("/api/me", catch_response=True) as response:
            if response.status_code == 429:
                response.failure("Rate limited (429)")
            elif response.status_code in [200, 401, 404]:
                response.success()
            else:
                response.failure(f"Unexpected status: {response.status_code}")


class AuthenticatedApiUser(HttpUser):
    """Simulates authenticated users hitting protected endpoints"""
    wait_time = between(0.05, 0.2)
    weight = 1
    
    def on_start(self):
        """Register and login to get a JWT token"""
        email = ''.join(random.choices(string.ascii_lowercase, k=10)) + "@test.com"
        password = "TestPassword123!"
        
        # Register
        self.client.post("/api/auth/register", json={
            "email": email,
            "password": password,
            "name": "Test User"
        })
        
        # Login
        response = self.client.post("/api/auth/login", json={
            "email": email,
            "password": password
        })
        
        if response.status_code == 200:
            self.token = response.json().get("token")
        else:
            self.token = None
    
    @task
    def hit_protected_endpoint(self):
        """Hit a protected endpoint with authentication"""
        if not self.token:
            return
            
        headers = {"Authorization": f"Bearer {self.token}"}
        with self.client.get("/api/me", headers=headers, catch_response=True) as response:
            if response.status_code == 429:
                response.failure("Rate limited (429)")
            elif response.status_code in [200, 401]:
                response.success()
            else:
                response.failure(f"Unexpected status: {response.status_code}")

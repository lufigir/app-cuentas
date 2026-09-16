def create(client, auth, payload, **overrides):
    return client.post("/api/accounts", json={**payload, **overrides}, headers=auth)


def test_health_endpoint_is_public(client):
    response = client.get("/")
    assert response.status_code == 200
    assert response.get_json()["service"] == "accounts-service"


def test_api_requires_api_key(client):
    response = client.get("/api/accounts")
    assert response.status_code == 401
    assert response.get_json() == {"error": "Invalid or missing API key"}


def test_api_rejects_wrong_api_key(client, payload):
    response = client.get("/api/accounts", headers={"X-API-Key": "nope"})
    assert response.status_code == 401


def test_create_account(client, auth, payload):
    response = create(client, auth, payload)
    assert response.status_code == 201

    body = response.get_json()
    assert body["id"] > 0
    assert body["first_name"] == "Ada"
    assert body["account_number"] == "ACC-0001"
    assert body["balance"] == 150.50


def test_create_account_defaults_balance_to_zero(client, auth, payload):
    payload.pop("balance")
    response = create(client, auth, payload)
    assert response.get_json()["balance"] == 0.0


def test_create_account_requires_fields(client, auth):
    response = client.post("/api/accounts", json={"first_name": "Ada"}, headers=auth)
    assert response.status_code == 400
    assert "Missing required fields" in response.get_json()["error"]


def test_create_account_validates_email(client, auth, payload):
    response = create(client, auth, payload, email="not-an-email")
    assert response.status_code == 400
    assert response.get_json()["error"] == "The field 'email' must be a valid email address"


def test_create_account_rejects_negative_balance(client, auth, payload):
    response = create(client, auth, payload, balance="-1")
    assert response.status_code == 400


def test_create_account_rejects_duplicate_account_number(client, auth, payload):
    create(client, auth, payload)
    response = create(client, auth, payload, email="other@example.com")
    assert response.status_code == 409


def test_list_accounts(client, auth, payload):
    create(client, auth, payload)
    create(client, auth, payload, account_number="ACC-0002", email="grace@example.com")

    response = client.get("/api/accounts", headers=auth)
    assert response.status_code == 200
    assert [account["account_number"] for account in response.get_json()] == [
        "ACC-0001",
        "ACC-0002",
    ]


def test_get_account(client, auth, payload):
    account_id = create(client, auth, payload).get_json()["id"]

    response = client.get(f"/api/accounts/{account_id}", headers=auth)
    assert response.status_code == 200
    assert response.get_json()["email"] == "ada@example.com"


def test_get_missing_account_returns_404(client, auth):
    response = client.get("/api/accounts/999", headers=auth)
    assert response.status_code == 404
    assert response.get_json() == {"error": "Account not found"}


def test_update_account(client, auth, payload):
    account_id = create(client, auth, payload).get_json()["id"]

    response = client.put(
        f"/api/accounts/{account_id}",
        json={"first_name": "Grace", "balance": "980.25"},
        headers=auth,
    )
    assert response.status_code == 200

    body = response.get_json()
    assert body["first_name"] == "Grace"
    assert body["last_name"] == "Lovelace"
    assert body["balance"] == 980.25


def test_update_requires_at_least_one_field(client, auth, payload):
    account_id = create(client, auth, payload).get_json()["id"]

    response = client.put(f"/api/accounts/{account_id}", json={}, headers=auth)
    assert response.status_code == 400
    assert response.get_json()["error"] == "No fields were provided to update"


def test_update_missing_account_returns_404(client, auth):
    response = client.put("/api/accounts/999", json={"first_name": "X"}, headers=auth)
    assert response.status_code == 404


def test_delete_account(client, auth, payload):
    account_id = create(client, auth, payload).get_json()["id"]

    response = client.delete(f"/api/accounts/{account_id}", headers=auth)
    assert response.status_code == 204
    assert client.get(f"/api/accounts/{account_id}", headers=auth).status_code == 404


def test_delete_missing_account_returns_404(client, auth):
    response = client.delete("/api/accounts/999", headers=auth)
    assert response.status_code == 404

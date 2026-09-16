from flask import Flask, jsonify, request

from config import Config
from errors import AppError
from extensions import db, migrate
from services import account_service


def create_app(config_class=Config):
    app = Flask(__name__)
    app.config.from_object(config_class)

    db.init_app(app)
    migrate.init_app(app, db)

    import models  # noqa: F401

    register_routes(app)
    register_error_handlers(app)

    return app


def register_routes(app):
    @app.get("/")
    def index():
        return {"status": "ok", "message": "API Flask funcionando"}

    @app.get("/api/accounts")
    def list_accounts():
        return jsonify(account_service.list_accounts())

    @app.get("/api/accounts/<int:account_id>")
    def get_account(account_id):
        return jsonify(account_service.get_account(account_id))

    @app.post("/api/accounts")
    def create_account():
        data, status = account_service.create_account(request.get_json(silent=True))
        return jsonify(data), status

    @app.put("/api/accounts/<int:account_id>")
    def update_account(account_id):
        return jsonify(account_service.update_account(account_id, request.get_json(silent=True)))

    @app.delete("/api/accounts/<int:account_id>")
    def delete_account(account_id):
        account_service.delete_account(account_id)
        return "", 204


def register_error_handlers(app):
    @app.errorhandler(AppError)
    def handle_app_error(error):
        return jsonify({"error": error.message}), error.status_code

    @app.errorhandler(404)
    def handle_not_found(_error):
        return jsonify({"error": "Resource not found"}), 404

    @app.errorhandler(500)
    def handle_internal_error(_error):
        return jsonify({"error": "Error interno del servidor"}), 500


app = create_app()

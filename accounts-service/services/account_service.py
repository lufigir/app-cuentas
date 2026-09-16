import re
from decimal import Decimal, InvalidOperation

from sqlalchemy.exc import IntegrityError

from errors import AppError, ConflictError, NotFoundError
from extensions import db
from models import Account

REQUIRED_FIELDS = ("first_name", "last_name", "email", "account_number")
UPDATABLE_FIELDS = ("first_name", "last_name", "email", "account_number", "balance")

MAX_LENGTHS = {
    "first_name": 100,
    "last_name": 100,
    "email": 150,
    "account_number": 20,
}

EMAIL_PATTERN = re.compile(r"^[^@\s]+@[^@\s]+\.[^@\s]+$")


def _validate_payload(data, *, partial=False):
    if not isinstance(data, dict):
        raise AppError("The request body must be a JSON object")

    if not partial:
        missing = [field for field in REQUIRED_FIELDS if not data.get(field)]
        if missing:
            raise AppError(f"Missing required fields: {', '.join(missing)}")

    cleaned = {}
    for field in UPDATABLE_FIELDS:
        if field not in data:
            continue

        value = data[field]
        if value is None or (isinstance(value, str) and not value.strip()):
            raise AppError(f"The field '{field}' cannot be empty")

        if field == "balance":
            try:
                balance = Decimal(str(value))
            except (InvalidOperation, ValueError):
                raise AppError("The field 'balance' must be a valid number")
            if balance < 0:
                raise AppError("The field 'balance' cannot be negative")
            cleaned[field] = balance
            continue

        text = str(value).strip()
        if len(text) > MAX_LENGTHS[field]:
            raise AppError(
                f"The field '{field}' must be at most {MAX_LENGTHS[field]} characters"
            )
        if field == "email" and not EMAIL_PATTERN.match(text):
            raise AppError("The field 'email' must be a valid email address")

        cleaned[field] = text

    return cleaned


def list_accounts():
    return [account.to_dict() for account in Account.query.order_by(Account.id).all()]


def get_account(account_id):
    account = db.session.get(Account, account_id)
    if account is None:
        raise NotFoundError("Account not found")
    return account.to_dict()


def create_account(data):
    payload = _validate_payload(data)

    account = Account(
        first_name=payload["first_name"],
        last_name=payload["last_name"],
        email=payload["email"],
        account_number=payload["account_number"],
        balance=payload.get("balance", Decimal("0.00")),
    )

    db.session.add(account)

    try:
        db.session.commit()
    except IntegrityError:
        db.session.rollback()
        raise ConflictError("An account with that account_number already exists")

    return account.to_dict(), 201


def update_account(account_id, data):
    account = db.session.get(Account, account_id)
    if account is None:
        raise NotFoundError("Account not found")

    payload = _validate_payload(data, partial=True)
    if not payload:
        raise AppError("No fields were provided to update")

    for field, value in payload.items():
        setattr(account, field, value)

    try:
        db.session.commit()
    except IntegrityError:
        db.session.rollback()
        raise ConflictError("An account with that account_number already exists")

    return account.to_dict()


def delete_account(account_id):
    account = db.session.get(Account, account_id)
    if account is None:
        raise NotFoundError("Account not found")

    db.session.delete(account)
    db.session.commit()

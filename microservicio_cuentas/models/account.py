from datetime import datetime, timezone
from decimal import Decimal

from extensions import db


class Account(db.Model):
    __tablename__ = "accounts"

    id = db.Column(db.Integer, primary_key=True, autoincrement=True)
    first_name = db.Column(db.String(100), nullable=False)
    last_name = db.Column(db.String(100), nullable=False)
    document = db.Column(db.String(30), nullable=False, unique=True, index=True)
    email = db.Column(db.String(150), nullable=False, unique=True, index=True)
    balance = db.Column(db.Numeric(15, 2), nullable=False, default=Decimal("0.00"))
    account_number = db.Column(db.String(50), nullable=False, unique=True, index=True)
    created_at = db.Column(
        db.DateTime,
        nullable=False,
        default=lambda: datetime.now(timezone.utc),
    )

    def __repr__(self):
        return f"<Account id={self.id} document={self.document} account={self.account_number}>"

    def to_dict(self):
        return {
            "id": self.id,
            "first_name": self.first_name,
            "last_name": self.last_name,
            "document": self.document,
            "email": self.email,
            "balance": float(self.balance),
            "account_number": self.account_number,
            "created_at": self.created_at.isoformat() if self.created_at else None,
        }

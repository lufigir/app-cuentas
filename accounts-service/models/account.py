from decimal import Decimal

from extensions import db


class Account(db.Model):
    __tablename__ = "accounts"

    id = db.Column(db.Integer, primary_key=True, autoincrement=True)
    first_name = db.Column(db.String(100), nullable=False)
    last_name = db.Column(db.String(100), nullable=False)
    email = db.Column(db.String(150), nullable=False)
    account_number = db.Column(db.String(20), nullable=False, unique=True, index=True)
    balance = db.Column(db.Numeric(12, 2), nullable=False, default=Decimal("0.00"))

    def __repr__(self):
        return f"<Account id={self.id} account_number={self.account_number}>"

    def to_dict(self):
        return {
            "id": self.id,
            "first_name": self.first_name,
            "last_name": self.last_name,
            "email": self.email,
            "account_number": self.account_number,
            "balance": float(self.balance),
        }

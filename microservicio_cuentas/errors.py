class AppError(Exception):
    def __init__(self, message, status_code=400):
        super().__init__(message)
        self.message = message
        self.status_code = status_code


class NotFoundError(AppError):
    def __init__(self, message="Resource not found"):
        super().__init__(message, status_code=404)


class ConflictError(AppError):
    def __init__(self, message="Conflict with an existing resource"):
        super().__init__(message, status_code=409)

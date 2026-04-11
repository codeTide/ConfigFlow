# handlers package — register all handlers by importing them
# /start migrated to PHP webhook (phase 1)
from . import callbacks   # noqa: F401  registers on_callback
from . import messages    # noqa: F401  registers universal_handler

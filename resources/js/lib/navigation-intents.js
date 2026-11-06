let pendingIntent = null;

export function setNavigationIntent(intent) {
  pendingIntent = intent;
}

export function consumeNavigationIntent(type) {
  if (pendingIntent?.type !== type) return null;

  const intent = pendingIntent;
  pendingIntent = null;

  return intent;
}

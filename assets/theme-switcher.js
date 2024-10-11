window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change',({ matches }) => {
  if (matches) {
    document.querySelector("html")?.setAttribute('data-theme', 'dark');
  } else {
    document.querySelector("html")?.setAttribute('data-theme', 'light');
  }
})

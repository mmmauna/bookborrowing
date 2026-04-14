  </main><!-- /.main -->
</div><!-- /.layout -->
<script>
// Auto-dismiss alerts after 4 seconds
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity .5s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 500);
  }, 4000);
});
</script>
</body>
</html>

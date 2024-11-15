<form action="{{ route('get-email') }}" method="POST">
    @csrf
    <input type="hidden" name="nif" value="{{ $nif }}">
    <input type="hidden" name="first_name" value="{{ $first_name }}">
    <input type="hidden" name="last_name" value="{{ $last_name }}">
    <input type="hidden" name="is_new" value="{{ $is_new }}">
</form>

<script>
    // Enviar el formulario automáticamente al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('form').submit();
    });
</script>

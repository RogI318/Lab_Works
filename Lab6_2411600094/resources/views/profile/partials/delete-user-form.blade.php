<section>
    <p class="text-muted small mb-3">
        Once your account is deleted, all of its resources and data will be permanently deleted.
        Before deleting your account, please download any data or information that you wish to retain.
    </p>

    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmUserDeletion">
        <i class="bi bi-trash me-1"></i> Delete Account
    </button>

    <div class="modal fade" id="confirmUserDeletion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" action="{{ route('profile.destroy') }}" class="modal-content">
                @csrf
                @method('delete')

                <div class="modal-header">
                    <h5 class="modal-title">Are you sure you want to delete your account?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p class="text-muted small">
                        Once your account is deleted, all of its resources and data will be permanently deleted.
                        Please enter your password to confirm you would like to permanently delete your account.
                    </p>

                    <input type="password" name="password"
                           class="form-control @if($errors->userDeletion->has('password')) is-invalid @endif"
                           placeholder="Password">
                    @if($errors->userDeletion->has('password'))
                        <div class="invalid-feedback">{{ $errors->userDeletion->first('password') }}</div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Account</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Re-open the modal if the password was wrong --}}
    @if($errors->userDeletion->isNotEmpty())
        <script>
            window.addEventListener('load', function () {
                new bootstrap.Modal(document.getElementById('confirmUserDeletion')).show();
            });
        </script>
    @endif
</section>
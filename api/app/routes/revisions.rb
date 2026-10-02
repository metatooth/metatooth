# frozen_string_literal: true

# The revisions endpoints.
class App
  options '/plans/:pid/revisions' do
    response['Access-Control-Allow-Origin'] = '*'
    response['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
    response['Access-Control-Allow-Methods'] = 'GET, POST'
  end

  options '/plans/:pid/revisions/:id' do
    response['Access-Control-Allow-Origin'] = '*'
    response['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
    response['Access-Control-Allow-Methods'] = 'GET, PUT, DELETE'
  end

  get '/plans/:pid/revisions' do
    plan = plan_repo.plan_with_revisions(params[:pid]).one!

    status 200
    { data: plan.revisions.to_a }.to_json
  end

  post '/plans/:pid/revisions' do
    plan = plan_repo.plan_with_revisions(params[:pid]).one!

    revision_hash = {}
    revision_hash[:plan_id] = plan.id
    revision_hash[:number] = plan.revisions.length
    revision_params.each do |k, v|
      revision_hash[k.to_sym] = v
    end

    puts "revision hash #{revision_hash}"

    errors = RevisionContract.new.call(revision_hash).errors(full: true).to_h

    if errors.empty?
      new_revision = revision_repo.create(revision_hash)

      response.headers['Location'] =
        "#{request.scheme}://#{request.host}/plans/#{plan.locator}/revisions/#{new_revision.locator}"

      status 201
      { data: new_revision.to_h }.to_json
    else
      unprocessable_entity!(errors)
    end
  end

  get '/plans/:pid/revisions/:id' do
    if revision
      status 200
      { data: revision.to_h }.to_json
    else
      resource_not_found
    end
  end

  put '/plans/:pid/revisions/:id' do
    if revision.nil?
      resource_not_found
    else
      revision_hash = revision.to_h
      revision_params.each do |k, v|
        revision_hash[k.to_sym] = v
      end

      errors = RevisionContract.new.call(revision_hash).errors(full: true).to_h

      if errors.empty?
        updated_revision = revision_repo.update(revision.id, revision_hash)
        status :ok
        { data: updated_revision.to_h }.to_json
      else
        unprocessable_entity!(errors)
      end
    end
  end

  delete '/plans/:pid/revisions/:id' do
    if revision.nil?
      resource_not_found
    else
      revision_repo.delete(revision.id)
      status :no_content
    end
  end

  private

  def plan_repo
    @plan_repo ||= PlanRepo.new(MAIN_CONTAINER)
  end

  def revision_repo
    @revision_repo ||= RevisionRepo.new(MAIN_CONTAINER)
  end

  def revision
    @revision ||= revision_repo.by_locator(params[:id])
  rescue StandardError
    nil
  end

  def revision_params
    begin
      request.body.rewind
      check = JSON.parse(request.body.read)
    rescue StandardError
      check = params
    end
    check['data']
      &.slice('bucket', 'etag', 'location', 'mime_type', 's3key', 'service')
  end
end
